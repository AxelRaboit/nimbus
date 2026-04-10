<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class DisallowedZipContentException extends RuntimeException
{
    /** @param string[] $disallowedFiles */
    public function __construct(private readonly array $disallowedFiles)
    {
        parent::__construct(sprintf('ZIP contains %d disallowed file(s)', count($disallowedFiles)));
    }

    /** @return string[] */
    public function getDisallowedFiles(): array
    {
        return $this->disallowedFiles;
    }
}
