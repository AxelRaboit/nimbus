<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\AllowedExtensionEnum;
use App\Enum\AllowedMimeTypeEnum;
use App\Exception\DisallowedFileTypeException;
use App\Exception\DisallowedZipContentException;
use App\Exception\FileLimitExceededException;
use App\Exception\SizeLimitExceededException;
use ZipArchive;

final readonly class TransferFileValidator
{
    public function __construct(
        private TusUploadServiceInterface $tusUploadService,
    ) {}

    /**
     * @param string[] $uploadKeys
     *
     * @throws FileLimitExceededException
     * @throws DisallowedFileTypeException
     * @throws DisallowedZipContentException
     * @throws SizeLimitExceededException
     */
    public function validate(array $uploadKeys, int $maxFiles, int $maxSizeMb): void
    {
        if (count($uploadKeys) > $maxFiles) {
            throw new FileLimitExceededException($maxFiles);
        }

        $totalSize = 0;

        foreach ($uploadKeys as $key) {
            $upload = $this->tusUploadService->getUpload((string) $key);
            if (null === $upload) {
                continue;
            }

            $totalSize += $upload['file_size'] ?? 0;

            $originalName = (string) ($upload['original_name'] ?? '');
            $ext = sprintf('.%s', mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION)));
            $mime = (string) ($upload['mime_type'] ?? '');

            if (!in_array($ext, AllowedExtensionEnum::values(), true) || !in_array($mime, AllowedMimeTypeEnum::values(), true)) {
                throw new DisallowedFileTypeException($originalName);
            }

            if (AllowedExtensionEnum::Zip->value === $ext) {
                $this->validateZipContents((string) ($upload['file_path'] ?? ''), $originalName);
            }
        }

        if ($totalSize > $maxSizeMb * 1024 * 1024) {
            throw new SizeLimitExceededException($maxSizeMb);
        }
    }

    /**
     * 100 MB compressed → hard cap on decompressed size to prevent zip bombs.
     * Also validates that each inner file has an allowed extension.
     */
    private const MAX_DECOMPRESSED_SIZE = 100 * 1024 * 1024;

    private function validateZipContents(string $filePath, string $originalName): void
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($filePath, ZipArchive::RDONLY)) {
            return;
        }

        $disallowed = [];
        $totalDecompressedSize = 0;

        for ($i = 0; $i < $zip->count(); ++$i) {
            $stat = $zip->statIndex($i);
            if (!$stat) {
                continue;
            }

            if (str_ends_with($stat['name'], '/')) {
                continue;
            }

            $totalDecompressedSize += $stat['size'];
            if ($totalDecompressedSize > self::MAX_DECOMPRESSED_SIZE) {
                $zip->close();
                throw new SizeLimitExceededException(self::MAX_DECOMPRESSED_SIZE / 1024 / 1024);
            }

            $innerExt = sprintf('.%s', mb_strtolower(pathinfo($stat['name'], PATHINFO_EXTENSION)));
            if (!in_array($innerExt, AllowedExtensionEnum::values(), true)) {
                $disallowed[] = $stat['name'];
            }
        }

        $zip->close();

        if ([] !== $disallowed) {
            throw new DisallowedZipContentException($disallowed);
        }
    }
}
