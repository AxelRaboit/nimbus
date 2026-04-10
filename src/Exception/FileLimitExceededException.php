<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class FileLimitExceededException extends RuntimeException
{
    public function __construct(int $maxFiles)
    {
        parent::__construct(sprintf('Trop de fichiers (maximum %d par transfert).', $maxFiles));
    }
}
