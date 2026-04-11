<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Recipient;
use App\Enum\TransferStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipient>
 */
class RecipientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipient::class);
    }

    public function countAll(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) FROM recipient');
    }

    public function countDownloaded(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) FROM recipient WHERE downloaded_at IS NOT NULL');
    }

    public function findByToken(string $token): ?Recipient
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @return Recipient[]
     */
    public function findPendingUnreminded(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.transfer', 't')
            ->where('r.downloadedAt IS NULL')
            ->andWhere('r.lastReminderSentAt IS NULL')
            ->andWhere('t.status = :status')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('status', TransferStatusEnum::Ready)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }
}
