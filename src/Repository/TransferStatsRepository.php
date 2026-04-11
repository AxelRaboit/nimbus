<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TransferStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferStats>
 */
class TransferStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferStats::class);
    }

    public function getSingleton(): TransferStats
    {
        $stats = $this->find(1);

        if (!$stats instanceof TransferStats) {
            $stats = new TransferStats();
            $this->getEntityManager()->persist($stats);
            $this->getEntityManager()->flush(); // first boot only — migration guarantees row exists in prod
        }

        return $stats;
    }

    public function increment(int $transfers = 0, int $files = 0, int $filesSize = 0, int $recipients = 0): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO transfer_stats (id, deleted_transfers_count, deleted_files_count, deleted_files_size, deleted_recipients_count)
             VALUES (1, :transfers, :files, :filesSize, :recipients)
             ON CONFLICT (id) DO UPDATE SET
                 deleted_transfers_count  = transfer_stats.deleted_transfers_count  + :transfers,
                 deleted_files_count      = transfer_stats.deleted_files_count      + :files,
                 deleted_files_size       = transfer_stats.deleted_files_size       + :filesSize,
                 deleted_recipients_count = transfer_stats.deleted_recipients_count + :recipients',
            [
                'transfers' => $transfers,
                'files' => $files,
                'filesSize' => $filesSize,
                'recipients' => $recipients,
            ]
        );
    }
}
