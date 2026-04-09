<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\TransferStatusEnum;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TransferManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private TusUploadService $tusUploadService,
        private string $transferStoragePath,
    ) {}

    /**
     * Finalizes a transfer: moves TUS tmp files to permanent storage,
     * attaches TransferFile entities, and marks the transfer as ready.
     *
     * @param string[] $uploadKeys TUS upload keys sent by the frontend
     */
    public function finalize(Transfer $transfer, array $uploadKeys): void
    {
        $storageDir = sprintf('%s/%s', $this->transferStoragePath, $transfer->getToken());

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0o750, true);
        }

        foreach ($uploadKeys as $uploadKey) {
            $upload = $this->tusUploadService->getUpload($uploadKey);

            if (null === $upload) {
                continue;
            }

            $filename = sprintf('%s_%s', bin2hex(random_bytes(8)), basename((string) $upload['file_path']));
            $destination = sprintf('%s/%s', $storageDir, $filename);

            rename($upload['file_path'], $destination);

            $file = new TransferFile();
            $file->setOriginalName($upload['original_name']);
            $file->setFilename($filename);
            $file->setMimeType($upload['mime_type']);
            $file->setFileSize($upload['file_size']);

            $transfer->addFile($file);
            $this->tusUploadService->deleteUpload($uploadKey);
        }

        $transfer->setStatus(TransferStatusEnum::Ready);
        $transfer->setTusUploadKey(null);

        $this->em->flush();
    }

    public function delete(Transfer $transfer): void
    {
        $storageDir = sprintf('%s/%s', $this->transferStoragePath, $transfer->getToken());

        if (is_dir($storageDir)) {
            foreach (glob(sprintf('%s/*', $storageDir)) ?: [] as $file) {
                unlink($file);
            }

            rmdir($storageDir);
        }

        $this->tusUploadService->deleteUploadsByTransferToken($transfer->getToken());

        $this->em->remove($transfer);
        $this->em->flush();
    }
}
