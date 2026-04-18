<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Transfer;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Manager\TransferManager;
use App\Model\Pagination;
use App\Repository\TransferRepository;
use App\Service\PlanService;
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
    #[Route('/transfers', name: 'transfer_api_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request, TransferRepository $transferRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $offset = max(0, (int) $request->query->get('offset', 0));
        $total = $transferRepository->countByUser($user);
        $pagination = Pagination::fromOffset($offset, limit: 10, total: $total);

        $transfers = $transferRepository->findByUser($user, $pagination->limit, $pagination->offset);
        $data = array_map($this->serializeTransferSummary(...), $transfers);

        return $this->json([
            'items' => $data,
            'hasMore' => $pagination->hasMore,
        ]);
    }

    #[Route('/transfer', name: 'transfer_api_create', methods: [HttpMethodEnum::Post->value])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        TransferManager $transferManager,
        PlanService $planService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $maxRecipients = $planService->getProMaxRecipients();
        $maxExpiryDays = $planService->getProMaxExpiryDays();
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

    #[Route('/transfer/{token}/finalize', name: 'transfer_api_finalize', methods: [HttpMethodEnum::Post->value])]
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

        $transferManager->finalize($transfer, $uploadKeys, $plainPassword);

        return $this->json([
            'status' => $transfer->getStatus()->value,
            'reference' => $transfer->getReference(),
            'downloadUrl' => $this->generateUrl('transfer_show', ['token' => $transfer->getToken()]),
            'manageUrl' => $this->generateUrl('transfer_manage', ['ownerToken' => $transfer->getOwnerToken()]),
        ]);
    }

    #[Route('/transfer/{token}/resume-check', name: 'transfer_api_resume_check', methods: [HttpMethodEnum::Get->value])]
    public function resumeCheck(string $token, TransferRepository $transferRepository, PlanService $planService): JsonResponse
    {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isPending()) {
            return $this->json(['resumable' => false], Response::HTTP_GONE);
        }

        $maxAgeHours = $planService->getTusCleanupMaxAgeHours();
        $threshold = new DateTimeImmutable(sprintf('-%d hours', $maxAgeHours));
        if ($transfer->getCreatedAt() < $threshold) {
            return $this->json(['resumable' => false], Response::HTTP_GONE);
        }

        return $this->json(['resumable' => true]);
    }

    #[Route('/transfer/{token}/abandon', name: 'transfer_api_abandon', methods: [HttpMethodEnum::Delete->value])]
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

    private function serializeTransferSummary(Transfer $transfer): array
    {
        return [
            'reference' => $transfer->getReference(),
            'ownerToken' => $transfer->getOwnerToken(),
            'token' => $transfer->getToken(),
            'status' => $transfer->getStatus()->value,
            'isPublic' => $transfer->isPublic(),
            'files' => array_map(fn ($transferFile): array => [
                'name' => $transferFile->getOriginalName(),
                'size' => $transferFile->getFileSize(),
            ], $transfer->getFiles()->toArray()),
            'recipients' => array_map(fn ($recipient): array => [
                'email' => $recipient->getEmail(),
                'downloaded' => $recipient->hasDownloaded(),
            ], $transfer->getRecipients()->toArray()),
            'publicDownloadCount' => $transfer->getPublicDownloadCount(),
            'expiresAt' => $transfer->getExpiresAt()->format(DateTimeInterface::ATOM),
            'createdAt' => $transfer->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    #[Route('/transfer/{token}', name: 'transfer_api_get', methods: [HttpMethodEnum::Get->value])]
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
            'files' => array_map(fn ($transferFile): array => [
                'name' => $transferFile->getOriginalName(),
                'size' => $transferFile->getFileSize(),
                'mimeType' => $transferFile->getMimeType(),
            ], $transfer->getFiles()->toArray()),
            'recipientCount' => $transfer->getRecipients()->count(),
        ]);
    }
}
