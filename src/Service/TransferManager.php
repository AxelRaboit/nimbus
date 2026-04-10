<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Entity\User;
use App\Enum\TransferStatusEnum;
use App\Repository\ApplicationParameterRepository;
use App\Repository\RecipientRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use ZipArchive;

final readonly class TransferManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TusUploadServiceInterface $tusUploadService,
        private TransferNotifierInterface $notifier,
        private TransferFileValidator $fileValidator,
        private RecipientRepository $recipientRepository,
        private string $transferStoragePath,
        private ApplicationParameterRepository $parameterRepository,
    ) {}

    /**
     * Creates and persists a new pending transfer from API data.
     *
     * @param array{
     *     senderEmail?: string|null,
     *     senderName?: string|null,
     *     message?: string|null,
     *     recipients?: string[],
     *     expiresInHours?: int|null,
     *     password?: string|null,
     *     isPublic?: bool,
     * } $data
     */
    public function create(array $data, ?User $user = null): Transfer
    {
        $transfer = new Transfer();
        $transfer->setSenderEmail($data['senderEmail'] ?? null);
        $transfer->setSenderName($data['senderName'] ?? null);
        $transfer->setMessage($data['message'] ?? null);
        $transfer->setIsPublic($data['isPublic'] ?? false);

        if ($user instanceof User) {
            $transfer->setUser($user);
        }

        if (!empty($data['expiresInHours'])) {
            $transfer->setExpiresAt(new DateTimeImmutable(sprintf('+%d hours', $data['expiresInHours'])));
        }

        if (!empty($data['password'])) {
            $transfer->setPasswordHash(password_hash($data['password'], PASSWORD_BCRYPT));
        }

        foreach ($data['recipients'] ?? [] as $email) {
            $recipient = new Recipient();
            $recipient->setEmail($email);
            $transfer->addRecipient($recipient);
        }

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        return $transfer;
    }

    /**
     * Validates and finalizes a transfer: moves TUS tmp files to permanent storage,
     * attaches TransferFile entities, and marks the transfer as ready.
     *
     * @param string[] $uploadKeys TUS upload keys sent by the frontend
     */
    public function finalize(Transfer $transfer, array $uploadKeys, ?string $plainPassword = null): void
    {
        $maxFiles = (int) $this->parameterRepository->get('max_files_per_transfer');
        $maxSizeMb = (int) $this->parameterRepository->get('max_transfer_size_mb');

        $this->fileValidator->validate($uploadKeys, $maxFiles, $maxSizeMb);

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

        $this->entityManager->flush();

        $this->notifier->notifyReady($transfer, $plainPassword);
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

        $filesCount = $transfer->getFiles()->count();
        $filesSize = $transfer->getTotalFilesSize();
        $recipientsCount = $transfer->getRecipients()->count();

        $this->entityManager->remove($transfer);
        $this->entityManager->flush();

        if ($filesCount > 0) {
            $this->parameterRepository->increment('stats.deleted_files_count', $filesCount);
            $this->parameterRepository->increment('stats.deleted_files_size', $filesSize);
        }

        if ($recipientsCount > 0) {
            $this->parameterRepository->increment('stats.deleted_recipients_count', $recipientsCount);
        }

        $this->parameterRepository->increment('stats.deleted_transfers_count');
    }

    public function findFileByFilename(Transfer $transfer, string $filename): ?TransferFile
    {
        foreach ($transfer->getFiles() as $file) {
            if ($file->getFilename() === $filename) {
                return $file;
            }
        }

        return null;
    }

    public function resolveFilePath(Transfer $transfer, TransferFile $file): string
    {
        return sprintf('%s/%s/%s', $this->transferStoragePath, $transfer->getToken(), $file->getFilename());
    }

    /**
     * Builds a temporary ZIP archive of all transfer files and returns its path.
     * The caller is responsible for deleting the file after sending.
     */
    public function buildDownloadZip(Transfer $transfer): string
    {
        $tmpZip = (string) tempnam(sys_get_temp_dir(), 'nimbus_zip_');

        $zip = new ZipArchive();
        $zip->open($tmpZip, ZipArchive::OVERWRITE);

        foreach ($transfer->getFiles() as $file) {
            $path = $this->resolveFilePath($transfer, $file);
            if (file_exists($path)) {
                $zip->addFile($path, $file->getOriginalName());
            }
        }

        $zip->close();

        return $tmpZip;
    }

    public function trackRecipientDownload(Transfer $transfer, string $recipientToken): void
    {
        $recipient = $this->recipientRepository->findByToken($recipientToken);

        if (!$recipient instanceof Recipient || $recipient->hasDownloaded()) {
            return;
        }

        $recipient->markAsDownloaded();
        $this->entityManager->flush();
        $this->notifier->notifyDownloaded($transfer, $recipient);
    }

    public function trackPublicDownload(Transfer $transfer): void
    {
        $transfer->incrementPublicDownloadCount();
        $this->entityManager->flush();
    }
}
