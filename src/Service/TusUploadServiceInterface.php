<?php

declare(strict_types=1);

namespace App\Service;

interface TusUploadServiceInterface
{
    public function getUpload(string $uploadKey): ?array;

    public function uploadExists(string $uploadKey): bool;

    public function deleteUpload(string $uploadKey): void;

    public function deleteUploadsByTransferToken(string $transferToken): void;

    public function cleanupOrphanedUploads(int $maxAgeSeconds = 86400): int;
}
