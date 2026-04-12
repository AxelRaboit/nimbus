<?php

declare(strict_types=1);

namespace App\Storage;

use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\ApplicationParameter\NimbusApplicationParameterEnum;
use App\Enum\StorageBackendEnum;
use App\Repository\ApplicationParameterRepository;
use Symfony\Component\HttpFoundation\Response;

final readonly class StorageManager
{
    public function __construct(
        private StorageAdapterInterface $local,
        private StorageAdapterInterface $r2,
        private ApplicationParameterRepository $params,
    ) {}

    public function getActiveBackend(): StorageBackendEnum
    {
        $value = $this->params->get(
            NimbusApplicationParameterEnum::StorageBackend->value,
            StorageBackendEnum::Local->value,
        );

        return StorageBackendEnum::tryFrom((string) $value) ?? StorageBackendEnum::Local;
    }

    public function getActiveAdapter(): StorageAdapterInterface
    {
        return $this->getAdapterFor($this->getActiveBackend());
    }

    public function getAdapterFor(StorageBackendEnum $backend): StorageAdapterInterface
    {
        return match ($backend) {
            StorageBackendEnum::Local => $this->local,
            StorageBackendEnum::R2 => $this->r2,
        };
    }

    public function getAdapterForFile(TransferFile $file): StorageAdapterInterface
    {
        return $this->getAdapterFor($file->getStorageBackend());
    }

    public function buildStorageKey(Transfer $transfer, string $filename): string
    {
        return sprintf('%s/%s', $transfer->getToken(), $filename);
    }

    public function fileExists(Transfer $transfer, TransferFile $file): bool
    {
        $storageKey = $this->buildStorageKey($transfer, $file->getFilename());

        return $this->getAdapterForFile($file)->exists($storageKey);
    }

    public function createFileResponse(Transfer $transfer, TransferFile $file, bool $inline): Response
    {
        $storageKey = $this->buildStorageKey($transfer, $file->getFilename());

        return $this->getAdapterForFile($file)->createDownloadResponse(
            $storageKey,
            $file->getOriginalName(),
            $file->getMimeType(),
            $inline,
        );
    }
}
