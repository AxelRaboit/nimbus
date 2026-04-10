<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApplicationParameter;
use App\Entity\Transfer;
use App\Enum\TransferStatusEnum;
use App\Model\Pagination;
use App\Repository\ApplicationParameterRepository;
use App\Repository\TransferRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class DevStatsService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransferRepository $transferRepository,
        private readonly ApplicationParameterRepository $parameterRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function getStats(): array
    {
        $conn = $this->em->getConnection();
        $now = new DateTimeImmutable();

        // ── Users ────────────────────────────────────────────────────────────
        $totalUsers = $this->userRepository->count([]);

        $newThisMonth = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM \"user\" WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())"
        );

        // ── Transfers ────────────────────────────────────────────────────────
        $byStatus = [];
        foreach (TransferStatusEnum::cases() as $status) {
            $byStatus[$status->value] = $this->transferRepository->count(['status' => $status]);
        }

        $activeTransfers = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM transfer WHERE status = 'ready' AND expires_at > NOW()"
        );

        // ── Files ────────────────────────────────────────────────────────────
        $liveFiles = (int) $conn->fetchOne('SELECT COUNT(*) FROM transfer_file');
        $liveSize = (int) ($conn->fetchOne('SELECT COALESCE(SUM(file_size), 0) FROM transfer_file') ?? 0);
        $totalFiles = $liveFiles + (int) $this->parameterRepository->get('stats.deleted_files_count', '0');
        $totalSize = $liveSize + (int) $this->parameterRepository->get('stats.deleted_files_size', '0');

        // ── Recipients ───────────────────────────────────────────────────────
        $liveRecipients = (int) $conn->fetchOne('SELECT COUNT(*) FROM recipient');
        $downloadedRecipients = (int) $conn->fetchOne('SELECT COUNT(*) FROM recipient WHERE downloaded_at IS NOT NULL');
        $totalRecipients = $liveRecipients + (int) $this->parameterRepository->get('stats.deleted_recipients_count', '0');

        // ── Transfers (all-time includes hard-deleted) ────────────────────────
        $totalTransfers = $this->transferRepository->count([])
            + (int) $this->parameterRepository->get('stats.deleted_transfers_count', '0');

        // ── Users by month (last 6 months) ───────────────────────────────────
        $usersByMonth = $this->fillMonths($conn->fetchAllAssociative(
            "SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') AS month, COUNT(*) AS count
             FROM \"user\"
             WHERE created_at >= DATE_TRUNC('month', NOW() - INTERVAL '5 months')
             GROUP BY month
             ORDER BY month"
        ), 6);

        // ── Transfers by month (last 6 months) ───────────────────────────────
        $transfersByMonth = $this->fillMonths($conn->fetchAllAssociative(
            "SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') AS month, COUNT(*) AS count
             FROM transfer
             WHERE created_at >= DATE_TRUNC('month', NOW() - INTERVAL '5 months')
             GROUP BY month
             ORDER BY month"
        ), 6);

        // ── Application parameters ───────────────────────────────────────────
        $parameters = $this->parameterRepository->findAllIndexed();

        return [
            'users' => [
                'total' => $totalUsers,
                'newThisMonth' => $newThisMonth,
            ],
            'transfers' => [
                'total' => $totalTransfers,
                'active' => $activeTransfers,
                'byStatus' => $byStatus,
            ],
            'files' => [
                'total' => $totalFiles,
                'totalSize' => $totalSize,
            ],
            'recipients' => [
                'total' => $totalRecipients,
                'downloaded' => $downloadedRecipients,
            ],
            'usersByMonth' => $usersByMonth,
            'transfersByMonth' => $transfersByMonth,
            'parameters' => array_map(
                fn (ApplicationParameter $p): array => [
                    'key' => $p->getKey(),
                    'value' => $p->getValue(),
                    'description' => $p->getDescription(),
                ],
                $parameters
            ),
        ];
    }

    public function getAllTransfers(int $page, string $status = ''): array
    {
        $qb = $this->transferRepository->createQueryBuilder('t')
            ->leftJoin('t.files', 'f')
            ->leftJoin('t.recipients', 'r')
            ->addSelect('f', 'r')
            ->orderBy('t.createdAt', 'DESC');

        if ('' !== $status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', TransferStatusEnum::from($status));
        }

        $countQb = $this->transferRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)');
        if ('' !== $status) {
            $countQb->andWhere('t.status = :status')
                    ->setParameter('status', TransferStatusEnum::from($status));
        }

        $pagination = Pagination::fromPage($page, limit: 20, total: (int) $countQb->getQuery()->getSingleScalarResult());
        $transfers = $qb->setMaxResults($pagination->limit)->setFirstResult($pagination->offset)->getQuery()->getResult();

        return [
            'items' => array_map($this->serializeTransfer(...), $transfers),
            'total' => $pagination->total,
            'page' => $pagination->page,
            'totalPages' => $pagination->totalPages,
        ];
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
            'downloadedCount' => $t->getRecipients()->filter(
                fn ($r): bool => $r->hasDownloaded()
            )->count(),
            'isPasswordProtected' => $t->isPasswordProtected(),
        ];
    }

    /**
     * Fills missing months with count=0 for the last $months months.
     *
     * @param array<array{month: string, count: string}> $rows
     */
    private function fillMonths(array $rows, int $months): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['month']] = (int) $row['count'];
        }

        $result = [];
        for ($i = $months - 1; $i >= 0; --$i) {
            $month = new DateTimeImmutable(sprintf('first day of -%d months', $i))->format('Y-m');
            $result[] = ['month' => $month, 'count' => $indexed[$month] ?? 0];
        }

        return $result;
    }
}
