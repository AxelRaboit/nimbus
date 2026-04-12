<?php

declare(strict_types=1);

namespace App\Storage;

use Symfony\Component\HttpFoundation\Response;

interface StorageAdapterInterface
{
    /**
     * Moves a file from a local temp path to the storage backend.
     * The source file is consumed (deleted or moved) by this operation.
     */
    public function store(string $sourcePath, string $storageKey): void;

    public function delete(string $storageKey): void;

    public function exists(string $storageKey): bool;

    public function createDownloadResponse(string $storageKey, string $originalName, ?string $mimeType, bool $inline): Response;

    /**
     * Returns a local filesystem path to the file for processing (e.g. ZIP building).
     * For remote backends, the file is downloaded to a temp location.
     * The caller is responsible for cleaning up temp files created by remote backends.
     */
    public function getLocalPath(string $storageKey): string;
}
