<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Repository\TransferRepository;
use App\Service\TransferManager;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('/transfer', name: 'transfer_api_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection(fields: [
            'senderEmail' => [new Assert\NotBlank(), new Assert\Email()],
            'senderName' => new Assert\Optional(new Assert\Length(max: 255)),
            'message' => new Assert\Optional(new Assert\Length(max: 2000)),
            'recipients' => [
                new Assert\NotBlank(),
                new Assert\Count(min: 1, max: 20),
                new Assert\All([new Assert\Email()]),
            ],
            'expiresInDays' => new Assert\Optional([
                new Assert\Range(min: 1, max: 30),
            ]),
            'password' => new Assert\Optional(new Assert\Length(min: 4, max: 128)),
        ]);

        $violations = $validator->validate($data ?? [], $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $transfer = new Transfer();
        $transfer->setSenderEmail($data['senderEmail']);
        $transfer->setSenderName($data['senderName'] ?? null);
        $transfer->setMessage($data['message'] ?? null);

        if (isset($data['expiresInDays'])) {
            $transfer->setExpiresAt(new DateTimeImmutable(sprintf('+%d days', $data['expiresInDays'])));
        }

        foreach ($data['recipients'] as $email) {
            $recipient = new Recipient();
            $recipient->setEmail($email);
            $transfer->addRecipient($recipient);
        }

        $em->persist($transfer);
        $em->flush();

        return $this->json([
            'token' => $transfer->getToken(),
            'ownerToken' => $transfer->getOwnerToken(),
            'reference' => $transfer->getReference(),
            'uploadKey' => $transfer->getToken(),
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

        if (empty($uploadKeys)) {
            return $this->json(['error' => 'No upload keys provided'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $transferManager->finalize($transfer, $uploadKeys);

        return $this->json([
            'status' => $transfer->getStatus()->value,
            'reference' => $transfer->getReference(),
            'downloadUrl' => $this->generateUrl('transfer_show', ['token' => $transfer->getToken()]),
            'manageUrl' => $this->generateUrl('transfer_manage', ['ownerToken' => $transfer->getOwnerToken()]),
        ]);
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
