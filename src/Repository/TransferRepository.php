<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transfer;
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
}
