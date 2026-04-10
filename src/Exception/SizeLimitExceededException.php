<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class SizeLimitExceededException extends RuntimeException
{
    public function __construct(int $maxSizeMb)
    {
        parent::__construct(sprintf('Transfert trop volumineux (maximum %d Mo).', $maxSizeMb));
    }
}
