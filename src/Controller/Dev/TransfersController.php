<?php

declare(strict_types=1);

namespace App\Controller\Dev;

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
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $status = $request->query->get('status', '');
        $result = $this->transferRepository->findPaginatedAdmin($page, $status);

        return $this->render('dev/index.html.twig', [
            'tab' => 'transfers',
            'transfers' => [
                ...$result,
                'items' => array_map($this->serialize(...), $result['items']),
            ],
            'status' => $status,
        ]);
    }

    private function serialize(Transfer $t): array
    {
        return [
            'id' => $t->getId(),
            'reference' => $t->getReference(),
            'ownerToken' => $t->getOwnerToken(),
            'senderEmail' => $t->getSenderEmail(),
            'senderName' => $t->getSenderName(),
            'status' => $t->getStatus()->value,
            'isExpired' => $t->isExpired(),
            'expiresAt' => $t->getExpiresAt()->format('c'),
            'createdAt' => $t->getCreatedAt()->format('c'),
            'filesCount' => $t->getFiles()->count(),
            'totalSize' => $t->getTotalFilesSize(),
            'recipientsCount' => $t->getRecipients()->count(),
            'downloadedCount' => $t->getRecipients()->filter(fn ($r): bool => $r->hasDownloaded())->count(),
            'isPasswordProtected' => $t->isPasswordProtected(),
        ];
    }
}
