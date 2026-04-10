<?php

declare(strict_types=1);

namespace App\Model;

final readonly class Pagination
{
    public int $offset;

    public int $totalPages;

    public bool $hasMore;

    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
    ) {
        $this->offset = ($page - 1) * $limit;
        $this->totalPages = (int) ceil($total / max(1, $limit));
        $this->hasMore = ($this->offset + $limit) < $total;
    }

    public static function fromPage(int $page, int $limit, int $total): self
    {
        return new self(max(1, $page), $limit, $total);
    }

    public static function fromOffset(int $offset, int $limit, int $total): self
    {
        $page = (int) floor(max(0, $offset) / max(1, $limit)) + 1;

        return new self($page, $limit, $total);
    }
}
