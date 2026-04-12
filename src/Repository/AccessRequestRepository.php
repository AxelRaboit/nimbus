<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use App\Model\Pagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessRequest>
 */
class AccessRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessRequest::class);
    }

    public function findByToken(string $token): ?AccessRequest
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findByAccessToken(string $accessToken): ?AccessRequest
    {
        return $this->findOneBy(['accessToken' => $accessToken]);
    }

    public function deleteProcessed(): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.status IN (:statuses)')
            ->setParameter('statuses', [AccessRequestStatusEnum::Approved, AccessRequestStatusEnum::Rejected])
            ->getQuery()
            ->execute();
    }

    /**
     * @return array{items: AccessRequest[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedAdmin(int $page = 1, int $limit = 20): array
    {
        $total = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pagination = Pagination::fromPage($page, $limit, $total);

        $items = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $pagination->page,
            'totalPages' => $pagination->totalPages,
        ];
    }
}
