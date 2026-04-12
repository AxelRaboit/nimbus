<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\ContentTypeEnum;
use App\Enum\HttpMethodEnum;
use App\Manager\TransferManager;
use App\Repository\RecipientRepository;
use App\Repository\TransferRepository;
use App\Service\TransferNotifierInterface;
use App\Storage\StorageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransferController extends AbstractController
{
    private const string SESSION_RECIPIENT_PREFIX = 'transfer_recipient_';

    private const string SESSION_UNLOCKED_PREFIX = 'transfer_unlocked_';

    #[Route('/t/{token}', name: 'transfer_show')]
    public function show(string $token, Request $request, TransferRepository $transferRepository, RecipientRepository $recipientRepository): Response
    {
        $recipient = $recipientRepository->findByToken($token);
        if ($recipient instanceof Recipient) {
            $transfer = $recipient->getTransfer();
            $request->getSession()->set(self::SESSION_RECIPIENT_PREFIX.$transfer->getToken(), $recipient->getToken());
        } else {
            $transfer = $transferRepository->findByToken($token);

            if ($transfer instanceof Transfer && !$transfer->isPublic()) {
                throw $this->createNotFoundException();
            }
        }

        if (!$transfer instanceof Transfer) {
            throw $this->createNotFoundException();
        }

        if (!$transfer->isReady() || $transfer->isExpired()) {
            return $this->render('transfer/unavailable.html.twig', ['transfer' => $transfer]);
        }

        if (!$this->isPasswordUnlocked($request, $transfer)) {
            return $this->render('transfer/password.html.twig', ['transfer' => $transfer]);
        }

        return $this->render('transfer/show.html.twig', ['transfer' => $transfer]);
    }

    #[Route('/t/{token}/unlock', name: 'transfer_unlock', methods: [HttpMethodEnum::Post->value])]
    public function unlock(string $token, Request $request, TransferRepository $transferRepository, TransferManager $transferManager, TranslatorInterface $translator, RateLimiterFactoryInterface $transferUnlockLimiter): JsonResponse
    {
        $limiter = $transferUnlockLimiter->create($request->getClientIp().'_'.$token);
        if (!$limiter->consume()->isAccepted()) {
            return new JsonResponse(['error' => $translator->trans('error.too_many_attempts')], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isReady() || $transfer->isExpired()) {
            return new JsonResponse(['error' => $translator->trans('error.not_found')], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('transfer_unlock_'.$transfer->getToken(), $body['_token'] ?? '')) {
            return new JsonResponse(['error' => $translator->trans('error.generic')]);
        }

        if (!$transferManager->verifyPassword($transfer, $body['password'] ?? '')) {
            return new JsonResponse(['error' => $translator->trans('transfer.show.password_error')]);
        }

        $request->getSession()->set(self::SESSION_UNLOCKED_PREFIX.$transfer->getToken(), true);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/t/{token}/download', name: 'transfer_download')]
    public function download(
        string $token,
        Request $request,
        TransferRepository $transferRepository,
        TransferManager $transferManager,
        StorageManager $storageManager,
    ): Response {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isReady() || $transfer->isExpired()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isPasswordUnlocked($request, $transfer)) {
            return $this->redirectToRoute('transfer_show', ['token' => $token]);
        }

        $recipientToken = $this->getSessionRecipientToken($request, $transfer);
        if ($recipientToken) {
            $transferManager->trackRecipientDownload($transfer, $recipientToken);
        } elseif ($transfer->isPublic()) {
            $transferManager->trackPublicDownload($transfer);
        }

        $files = $transfer->getFiles()->toArray();

        if (1 === count($files)) {
            return $storageManager->createFileResponse($transfer, $files[0], inline: false);
        }

        $zipPath = $transferManager->buildDownloadZip($transfer);
        $zipName = sprintf('nimbus-%s.zip', mb_strtolower($transfer->getReference()));

        $response = new BinaryFileResponse(
            $zipPath,
            Response::HTTP_OK,
            [
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $zipName),
                'Content-Type' => ContentTypeEnum::Zip->value,
            ],
        );

        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/t/{token}/download/{filename}', name: 'transfer_download_file')]
    public function downloadFile(
        string $token,
        string $filename,
        Request $request,
        TransferRepository $transferRepository,
        TransferManager $transferManager,
        StorageManager $storageManager,
    ): Response {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isReady() || $transfer->isExpired()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isPasswordUnlocked($request, $transfer)) {
            return $this->redirectToRoute('transfer_show', ['token' => $token]);
        }

        $file = $transferManager->findFileByFilename($transfer, $filename);

        if (!$file instanceof TransferFile) {
            throw $this->createNotFoundException();
        }

        if (!$storageManager->fileExists($transfer, $file)) {
            throw $this->createNotFoundException();
        }

        $recipientToken = $this->getSessionRecipientToken($request, $transfer);
        if ($recipientToken) {
            $transferManager->trackRecipientDownload($transfer, $recipientToken);
        } elseif ($transfer->isPublic()) {
            $transferManager->trackPublicDownload($transfer);
        }

        return $storageManager->createFileResponse($transfer, $file, inline: false);
    }

    #[Route('/t/{token}/preview/{filename}', name: 'transfer_preview_file')]
    public function previewFile(
        string $token,
        string $filename,
        Request $request,
        TransferRepository $transferRepository,
        TransferManager $transferManager,
        StorageManager $storageManager,
    ): Response {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isReady() || $transfer->isExpired()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isPasswordUnlocked($request, $transfer)) {
            throw $this->createAccessDeniedException();
        }

        $file = $transferManager->findFileByFilename($transfer, $filename);

        if (!$file instanceof TransferFile) {
            throw $this->createNotFoundException();
        }

        if (!$storageManager->fileExists($transfer, $file)) {
            throw $this->createNotFoundException();
        }

        return $storageManager->createFileResponse($transfer, $file, inline: true);
    }

    #[Route('/manage/{ownerToken}', name: 'transfer_manage')]
    public function manage(string $ownerToken, TransferRepository $transferRepository): Response
    {
        $transfer = $transferRepository->findByOwnerToken($ownerToken);

        if (!$transfer instanceof Transfer) {
            throw $this->createNotFoundException();
        }

        return $this->render('transfer/manage.html.twig', [
            'transfer' => $transfer,
            'csrf_token' => $this->container->get('security.csrf.token_manager')->getToken('transfer_delete_'.$transfer->getOwnerToken())->getValue(),
        ]);
    }

    #[Route('/api/manage/{ownerToken}/remind', name: 'transfer_remind', methods: [HttpMethodEnum::Post->value])]
    public function remind(
        string $ownerToken,
        Request $request,
        TransferRepository $transferRepository,
        TransferNotifierInterface $notifier,
    ): JsonResponse {
        $transfer = $transferRepository->findByOwnerToken($ownerToken);

        if (!$transfer instanceof Transfer) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$transfer->isReady()) {
            return $this->json(['error' => 'Transfer not ready'], Response::HTTP_BAD_REQUEST);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $email = isset($body['email']) ? (string) $body['email'] : null;

        if (null === $email) {
            return $this->json(['error' => 'Missing email'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        foreach ($transfer->getRecipients() as $recipient) {
            if ($recipient->getEmail() === $email && !$recipient->hasDownloaded()) {
                $notifier->notifyReminder($transfer, $recipient);

                return $this->json(['ok' => true]);
            }
        }

        return $this->json(['error' => 'Recipient not found or already downloaded'], Response::HTTP_NOT_FOUND);
    }

    private function isPasswordUnlocked(Request $request, Transfer $transfer): bool
    {
        if (!$transfer->isPasswordProtected()) {
            return true;
        }

        return (bool) $request->getSession()->get(self::SESSION_UNLOCKED_PREFIX.$transfer->getToken());
    }

    private function getSessionRecipientToken(Request $request, Transfer $transfer): ?string
    {
        return $request->getSession()->get(self::SESSION_RECIPIENT_PREFIX.$transfer->getToken()) ?: null;
    }

    #[Route('/manage/{ownerToken}/delete', name: 'transfer_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(string $ownerToken, Request $request, TransferRepository $transferRepository, TransferManager $transferManager, TranslatorInterface $translator): Response
    {
        $transfer = $transferRepository->findByOwnerToken($ownerToken);

        if (!$transfer instanceof Transfer) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('transfer_delete_'.$ownerToken, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($translator->trans('error.csrf_invalid'));
        }

        $transferManager->delete($transfer);

        $this->addFlash('success', $translator->trans('transfer.manage.deleted'));

        return $this->redirectToRoute('home');
    }
}
