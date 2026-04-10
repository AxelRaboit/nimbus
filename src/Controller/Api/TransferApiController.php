<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Transfer;
use App\Entity\User;
use App\Exception\DisallowedFileTypeException;
use App\Exception\DisallowedZipContentException;
use App\Exception\FileLimitExceededException;
use App\Exception\SizeLimitExceededException;
use App\Repository\ApplicationParameterRepository;
use App\Repository\TransferRepository;
use App\Service\TransferManager;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class TransferApiController extends AbstractController
{
    #[Route('/transfers', name: 'transfer_api_list', methods: ['GET'])]
    public function list(Request $request, TransferRepository $transferRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = 10;
        $offset = max(0, (int) $request->query->get('offset', 0));

        $transfers = $transferRepository->findByUser($user, $limit, $offset);
        $total = $transferRepository->countByUser($user);

        $data = array_map(function (Transfer $t): array {
            $recipients = array_map(fn ($r): array => [
                'email' => $r->getEmail(),
                'downloaded' => $r->hasDownloaded(),
            ], $t->getRecipients()->toArray());

            $files = array_map(fn ($f): array => [
                'name' => $f->getOriginalName(),
                'size' => $f->getFileSize(),
            ], $t->getFiles()->toArray());

            return [
                'reference' => $t->getReference(),
                'ownerToken' => $t->getOwnerToken(),
                'token' => $t->getToken(),
                'status' => $t->getStatus()->value,
                'isPublic' => $t->isPublic(),
                'files' => $files,
                'recipients' => $recipients,
                'publicDownloadCount' => $t->getPublicDownloadCount(),
                'expiresAt' => $t->getExpiresAt()->format(DateTimeInterface::ATOM),
                'createdAt' => $t->getCreatedAt()->format(DateTimeInterface::ATOM),
            ];
        }, $transfers);

        return $this->json([
            'items' => $data,
            'hasMore' => ($offset + $limit) < $total,
        ]);
    }

    #[Route('/transfer', name: 'transfer_api_create', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        ApplicationParameterRepository $params,
        TransferManager $transferManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $maxRecipients = (int) $params->get('max_recipients_per_transfer');
        $maxExpiryDays = (int) $params->get('max_expiry_days');
        $isPublic = isset($data['isPublic']) && true === $data['isPublic'];

        $fields = [
            'senderEmail' => new Assert\Optional([new Assert\Email()]),
            'senderName' => new Assert\Optional(new Assert\Length(max: 255)),
            'message' => new Assert\Optional(new Assert\Length(max: 2000)),
            'isPublic' => new Assert\Optional(new Assert\Type('bool')),
            'expiresInHours' => new Assert\Optional([
                new Assert\Range(min: 1, max: $maxExpiryDays * 24),
            ]),
            'password' => new Assert\Optional(new Assert\Length(min: 4, max: 128)),
        ];

        if ($isPublic) {
            $fields['recipients'] = new Assert\Optional();
        } else {
            $fields['senderEmail'] = [new Assert\NotBlank(), new Assert\Email()];
            $fields['recipients'] = [
                new Assert\NotBlank(),
                new Assert\Count(min: 1, max: $maxRecipients),
                new Assert\All([new Assert\Email()]),
            ];
        }

        $violations = $validator->validate($data ?? [], new Assert\Collection(fields: $fields));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getUser();
        $transfer = $transferManager->create(
            array_merge($data, ['isPublic' => $isPublic]),
            $user instanceof User ? $user : null,
        );

        return $this->json([
            'token' => $transfer->getToken(),
            'ownerToken' => $transfer->getOwnerToken(),
            'reference' => $transfer->getReference(),
            'uploadKey' => $transfer->getToken(),
            'isPublic' => $transfer->isPublic(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/transfer/{token}/finalize', name: 'transfer_api_finalize', methods: ['POST'])]
    public function finalize(
        string $token,
        Request $request,
        TransferRepository $transferRepository,
        TransferManager $transferManager,
    ): JsonResponse {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$transfer->isPending()) {
            return $this->json(['error' => 'Transfer already finalized'], Response::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true);
        $uploadKeys = $data['uploadKeys'] ?? [];
        $plainPassword = isset($data['password']) && '' !== $data['password'] ? (string) $data['password'] : null;

        if (empty($uploadKeys)) {
            return $this->json(['error' => 'No upload keys provided'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $transferManager->finalize($transfer, $uploadKeys, $plainPassword);
        } catch (FileLimitExceededException|SizeLimitExceededException|DisallowedFileTypeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DisallowedZipContentException $e) {
            return $this->json(
                ['error' => 'zip_content_not_allowed', 'disallowed_files' => $e->getDisallowedFiles()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json([
            'status' => $transfer->getStatus()->value,
            'reference' => $transfer->getReference(),
            'downloadUrl' => $this->generateUrl('transfer_show', ['token' => $transfer->getToken()]),
            'manageUrl' => $this->generateUrl('transfer_manage', ['ownerToken' => $transfer->getOwnerToken()]),
        ]);
    }

    #[Route('/transfer/{token}/resume-check', name: 'transfer_api_resume_check', methods: ['GET'])]
    public function resumeCheck(string $token, TransferRepository $transferRepository): JsonResponse
    {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isPending()) {
            return $this->json(['resumable' => false], Response::HTTP_GONE);
        }

        $threshold = new DateTimeImmutable('-48 hours');
        if ($transfer->getCreatedAt() < $threshold) {
            return $this->json(['resumable' => false], Response::HTTP_GONE);
        }

        return $this->json(['resumable' => true]);
    }

    #[Route('/transfer/{token}/abandon', name: 'transfer_api_abandon', methods: ['DELETE'])]
    public function abandon(
        string $token,
        TransferRepository $transferRepository,
        TransferManager $transferManager,
    ): JsonResponse {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isPending()) {
            return $this->json(['ok' => true]);
        }

        $transferManager->delete($transfer);

        return $this->json(['ok' => true]);
    }

    #[Route('/transfer/{token}', name: 'transfer_api_get', methods: ['GET'])]
    public function get(string $token, TransferRepository $transferRepository): JsonResponse
    {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'reference' => $transfer->getReference(),
            'status' => $transfer->getStatus()->value,
            'expiresAt' => $transfer->getExpiresAt()->format(DateTimeInterface::ATOM),
            'files' => array_map(fn ($f): array => [
                'name' => $f->getOriginalName(),
                'size' => $f->getFileSize(),
                'mimeType' => $f->getMimeType(),
            ], $transfer->getFiles()->toArray()),
            'recipientCount' => $transfer->getRecipients()->count(),
        ]);
    }
}
