<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\DTO\PaginationRequest;
use App\Entity\Transfer;
use App\Enum\UserRoleEnum;
use App\Repository\TransferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/transfers')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class TransfersController extends AbstractController
{
    public function __construct(
        private readonly TransferRepository $transferRepository,
    ) {}

    #[Route('', name: 'dev_transfers')]
    public function index(Request $request, PaginationRequest $pagination): Response
    {
        $status = $request->query->getString('status');
        $result = $this->transferRepository->findPaginatedAdmin($pagination->page, $status);

        return $this->render('dev/index.html.twig', [
            'tab' => 'transfers',
            'transfers' => [
                ...$result,
                'items' => array_map($this->serialize(...), $result['items']),
            ],
            'status' => $status,
        ]);
    }

    private function serialize(Transfer $transfer): array
    {
        return [
            'id' => $transfer->getId(),
            'reference' => $transfer->getReference(),
            'ownerToken' => $transfer->getOwnerToken(),
            'senderEmail' => $transfer->getSenderEmail(),
            'senderName' => $transfer->getSenderName(),
            'status' => $transfer->getStatus()->value,
            'isExpired' => $transfer->isExpired(),
            'expiresAt' => $transfer->getExpiresAt()->format('c'),
            'createdAt' => $transfer->getCreatedAt()->format('c'),
            'filesCount' => $transfer->getFiles()->count(),
            'totalSize' => $transfer->getTotalFilesSize(),
            'recipientsCount' => $transfer->getRecipients()->count(),
            'downloadedCount' => $transfer->getRecipients()->filter(fn ($recipient): bool => $recipient->hasDownloaded())->count(),
            'isPasswordProtected' => $transfer->isPasswordProtected(),
        ];
    }
}
