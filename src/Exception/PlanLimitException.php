<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class PlanLimitException extends RuntimeException
{
    public function __construct(string $limitKey)
    {
        parent::__construct(sprintf('Plan limit reached: %s', $limitKey));
    }
}
