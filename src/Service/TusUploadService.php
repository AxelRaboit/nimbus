<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\FallbackMimeTypeEnum;
use TusPhp\Cache\FileStore as TusFileStore;

final readonly class TusUploadService
{
    public function __construct(
        private string $tusCachePath,
        private string $tusUploadPath,
    ) {}

    public function getUpload(string $uploadKey): ?array
    {
        $cache = new TusFileStore($this->tusCachePath);
        $cache->setPrefix('tus:server:');

        $meta = $cache->get($uploadKey);

        if (!$meta || empty($meta['file_path'])) {
            return null;
        }

        if (!file_exists($meta['file_path'])) {
            return null;
        }

        return [
            'file_path' => $meta['file_path'],
            'original_name' => $meta['metadata']['originalName'] ?? $meta['name'] ?? 'file',
            'mime_type' => $meta['metadata']['filetype'] ?? FallbackMimeTypeEnum::OctetStream->value,
            'file_size' => (int) ($meta['size'] ?? 0),
        ];
    }

    public function uploadExists(string $uploadKey): bool
    {
        $cache = new TusFileStore($this->tusCachePath);
        $cache->setPrefix('tus:server:');

        $meta = $cache->get($uploadKey);

        return null !== $meta && !empty($meta['file_path']) && file_exists($meta['file_path']);
    }

    public function deleteUpload(string $uploadKey): void
    {
        $cache = new TusFileStore($this->tusCachePath);
        $cache->setPrefix('tus:server:');
        $cache->delete($uploadKey);
    }

    public function deleteUploadsByTransferToken(string $transferToken): void
    {
        $cache = new TusFileStore($this->tusCachePath);
        $cache->setPrefix('tus:server:');

        foreach ($cache->keys() as $key) {
            $entry = $cache->get($key);
            if (!is_array($entry)) {
                continue;
            }

            if (($entry['metadata']['transferToken'] ?? null) !== $transferToken) {
                continue;
            }

            if (!empty($entry['file_path']) && file_exists($entry['file_path'])) {
                unlink($entry['file_path']);
            }

            $cache->delete($key);
        }
    }

    public function cleanupOrphanedUploads(int $maxAgeSeconds = 86400): int
    {
        $cleaned = 0;
        $now = time();

        foreach (glob(sprintf('%s/*', $this->tusUploadPath)) ?: [] as $uploadFile) {
            if (is_file($uploadFile) && ($now - filemtime($uploadFile)) > $maxAgeSeconds) {
                unlink($uploadFile);
                ++$cleaned;
            }
        }

        return $cleaned;
    }
}
