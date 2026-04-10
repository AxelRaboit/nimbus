<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transfer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transfer>
 */
class TransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transfer::class);
    }

    public function findByToken(string $token): ?Transfer
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findByOwnerToken(string $ownerToken): ?Transfer
    {
        return $this->findOneBy(['ownerToken' => $ownerToken]);
    }

    /**
     * @return Transfer[]
     */
    public function findByUser(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.files', 'f')
            ->leftJoin('t.recipients', 'r')
            ->addSelect('f', 'r')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
