<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class DisallowedFileTypeException extends RuntimeException
{
    public function __construct(string $filename)
    {
        parent::__construct(sprintf('Type de fichier non autorisé : %s', $filename));
    }
}
