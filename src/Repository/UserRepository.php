<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Repository\Trait\PaginationTrait;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findDemoUser(): ?User
    {
        return $this->findOneBy(['isDemo' => true]);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array{items: User[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedForAdmin(int $page, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');
        $countQb = $this->createQueryBuilder('u')->select('COUNT(u.id)');

        if ($search) {
            $condition = 'LOWER(u.name) LIKE :search OR LOWER(u.email) LIKE :search';
            $param = '%'.mb_strtolower($search).'%';
            $qb->andWhere($condition)->setParameter('search', $param);
            $countQb->andWhere($condition)->setParameter('search', $param);
        }

        return $this->paginate($qb, $countQb, $page);
    }

    public function countNewThisMonth(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM \"users\" WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())"
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
                 FROM \"users\"
                 WHERE created_at >= DATE_TRUNC('month', NOW() - INTERVAL '%d months')
                 GROUP BY month
                 ORDER BY month",
                $months - 1
            )
        );

        return $this->fillMonths($rows, $months);
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
