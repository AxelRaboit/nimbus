<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApplicationParameter;
use App\Repository\Trait\PaginationTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicationParameter>
 */
class ApplicationParameterRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationParameter::class);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $param = $this->find($key);

        return $param?->getValue() ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        $param = $this->find($key);

        if (!$param instanceof ApplicationParameter) {
            $param = new ApplicationParameter($key, $value);
            $this->getEntityManager()->persist($param);
        } else {
            $param->setValue($value);
        }

        $this->getEntityManager()->flush();
    }

    public function increment(string $key, int $by = 1): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO application_parameters (key, value) VALUES (:key, :by)
             ON CONFLICT (key) DO UPDATE SET value = (COALESCE(application_parameters.value, \'0\')::bigint + :by)::text',
            ['key' => $key, 'by' => $by]
        );
    }

    /**
     * @return ApplicationParameter[]
     */
    public function findAllIndexed(): array
    {
        return $this->findAll();
    }

    /**
     * @return array{items: ApplicationParameter[], total: int, page: int, totalPages: int}
     */
    public function findPaginated(int $page): array
    {
        $queryBuilder = $this->createQueryBuilder('p')->orderBy('p.key', 'ASC');
        $countQueryBuilder = $this->createQueryBuilder('p')->select('COUNT(p.key)');

        return $this->paginate($queryBuilder, $countQueryBuilder, $page);
    }
}
