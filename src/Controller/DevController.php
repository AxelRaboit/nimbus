<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transfer;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\ApplicationParameterRepository;
use App\Repository\RecipientRepository;
use App\Repository\TransferFileRepository;
use App\Repository\TransferRepository;
use App\Repository\TransferStatsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev')]
#[IsGranted(UserRoleEnum::Dev->value)]
class DevController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransferRepository $transferRepository,
        private readonly TransferFileRepository $transferFileRepository,
        private readonly RecipientRepository $recipientRepository,
        private readonly TransferStatsRepository $transferStatsRepository,
        private readonly ApplicationParameterRepository $parameterRepository,
    ) {}

    #[Route('', name: 'dev_dashboard')]
    public function dashboard(): Response
    {
        $historicalStats = $this->transferStatsRepository->getSingleton();

        return $this->render('dev/index.html.twig', [
            'tab' => 'stats',
            'stats' => [
                'users' => [
                    'total' => $this->userRepository->count([]),
                    'newThisMonth' => $this->userRepository->countNewThisMonth(),
                ],
                'transfers' => [
                    'total' => $this->transferRepository->count([]) + $historicalStats->getDeletedTransfersCount(),
                    'active' => $this->transferRepository->countActive(),
                    'byStatus' => $this->transferRepository->count([]),
                ],
                'files' => [
                    'total' => $this->transferFileRepository->countAll() + $historicalStats->getDeletedFilesCount(),
                    'totalSize' => $this->transferFileRepository->sumSize() + $historicalStats->getDeletedFilesSize(),
                ],
                'recipients' => [
                    'total' => $this->recipientRepository->countAll() + $historicalStats->getDeletedRecipientsCount(),
                    'downloaded' => $this->recipientRepository->countDownloaded(),
                ],
                'usersByMonth' => $this->userRepository->countByMonth(6),
                'transfersByMonth' => $this->transferRepository->countByMonth(6),
                'parameters' => array_map(
                    fn ($p): array => ['key' => $p->getKey(), 'value' => $p->getValue(), 'description' => $p->getDescription()],
                    $this->parameterRepository->findAllIndexed()
                ),
            ],
        ]);
    }

    #[Route('/parameters/{key}', name: 'dev_parameter_update', methods: [HttpMethodEnum::Patch->value])]
    public function updateParameter(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        $this->parameterRepository->set($key, $value);

        return $this->json(['key' => $key, 'value' => $value]);
    }

    #[Route('/transfers', name: 'dev_transfers')]
    public function transfers(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $status = $request->query->get('status', '');

        $result = $this->transferRepository->findPaginatedAdmin($page, $status);

        return $this->render('dev/index.html.twig', [
            'tab' => 'transfers',
            'transfers' => [
                ...$result,
                'items' => array_map($this->serializeTransfer(...), $result['items']),
            ],
            'status' => $status,
        ]);
    }

    private function serializeTransfer(Transfer $t): array
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
