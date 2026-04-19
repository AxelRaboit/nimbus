<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TransferFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferFile>
 */
class TransferFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferFile::class);
    }

    public function countAll(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) FROM transfer_files');
    }

    public function sumSize(): int
    {
        return (int) ($this->getEntityManager()->getConnection()->fetchOne('SELECT COALESCE(SUM(file_size), 0) FROM transfer_files') ?? 0);
    }
}
