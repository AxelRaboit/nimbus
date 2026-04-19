<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transfer;
use App\Entity\User;
use App\Enum\TransferStatusEnum;
use App\Repository\Trait\PaginationTrait;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transfer>
 */
class TransferRepository extends ServiceEntityRepository
{
    use PaginationTrait;

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

    public function countActive(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            sprintf("SELECT COUNT(*) FROM transfer WHERE status = '%s' AND expires_at > NOW()", TransferStatusEnum::Ready->value)
        );
    }

    /**
     * @return array<array{month: string, count: int}>
     */
    public function countByMonth(int $months = 6): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            sprintf(
                "SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') AS month, COUNT(*) AS count
                 FROM transfer
                 WHERE created_at >= DATE_TRUNC('month', NOW() - INTERVAL '%d months')
                 GROUP BY month
                 ORDER BY month",
                $months - 1
            )
        );

        return $this->fillMonths($rows, $months);
    }

    /**
     * @return array{items: Transfer[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedAdmin(int $page, string $status = ''): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.files', 'f')
            ->leftJoin('t.recipients', 'r')
            ->addSelect('f', 'r')
            ->orderBy('t.createdAt', 'DESC');

        $countQb = $this->createQueryBuilder('t')->select('COUNT(t.id)');

        if ('' !== $status) {
            $qb->andWhere('t.status = :status')->setParameter('status', TransferStatusEnum::from($status));
            $countQb->andWhere('t.status = :status')->setParameter('status', TransferStatusEnum::from($status));
        }

        return $this->paginate($qb, $countQb, $page);
    }

    /**
     * @param array<array{month: string, count: string}> $rows
     *
     * @return array<array{month: string, count: int}>
     */
    private function fillMonths(array $rows, int $months): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['month']] = (int) $row['count'];
        }

        $result = [];
        for ($monthOffset = $months - 1; $monthOffset >= 0; --$monthOffset) {
            $month = new DateTimeImmutable(sprintf('first day of -%d months', $monthOffset))->format('Y-m');
            $result[] = ['month' => $month, 'count' => $indexed[$month] ?? 0];
        }

        return $result;
    }
}
