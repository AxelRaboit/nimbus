<?php

declare(strict_types=1);

namespace App\Storage;

use App\Enum\ContentTypeEnum;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

final readonly class LocalStorageAdapter implements StorageAdapterInterface
{
    private const int DIRECTORY_PERMISSIONS = 0o750;

    public function __construct(private string $transferStoragePath) {}

    public function store(string $sourcePath, string $storageKey): void
    {
        $destination = $this->fullPath($storageKey);
        $directory = dirname($destination);

        if (!is_dir($directory)) {
            mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
        }

        rename($sourcePath, $destination);
    }

    public function delete(string $storageKey): void
    {
        $path = $this->fullPath($storageKey);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function exists(string $storageKey): bool
    {
        return file_exists($this->fullPath($storageKey));
    }

    public function createDownloadResponse(string $storageKey, string $originalName, ?string $mimeType, bool $inline): Response
    {
        return new BinaryFileResponse(
            $this->fullPath($storageKey),
            Response::HTTP_OK,
            [
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
                    $originalName,
                ),
                'Content-Type' => $mimeType ?: ContentTypeEnum::OctetStream->value,
            ],
        );
    }

    public function getLocalPath(string $storageKey): string
    {
        return $this->fullPath($storageKey);
    }

    private function fullPath(string $storageKey): string
    {
        return sprintf('%s/%s', $this->transferStoragePath, $storageKey);
    }
}
