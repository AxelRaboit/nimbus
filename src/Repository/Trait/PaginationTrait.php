<?php

declare(strict_types=1);

namespace App\Repository\Trait;

use App\Model\Pagination;
use Doctrine\ORM\QueryBuilder;

trait PaginationTrait
{
    private function paginate(
        QueryBuilder $queryBuilder,
        QueryBuilder $countQueryBuilder,
        int $page,
        int $limit = 20,
    ): array {
        $total = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
        $pagination = Pagination::fromPage($page, $limit, $total);

        $items = $queryBuilder
            ->setMaxResults($pagination->limit)
            ->setFirstResult($pagination->offset)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $pagination->total,
            'page' => $pagination->page,
            'totalPages' => $pagination->totalPages,
        ];
    }
}
