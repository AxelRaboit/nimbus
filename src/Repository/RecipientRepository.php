<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Recipient;
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

    public function findByToken(string $token): ?Recipient
    {
        return $this->findOneBy(['token' => $token]);
    }
}
