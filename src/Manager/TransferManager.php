<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Entity\User;
use App\Enum\StorageBackendEnum;
use App\Enum\TransferStatusEnum;
use App\Exception\SizeLimitExceededException;
use App\Repository\RecipientRepository;
use App\Repository\TransferRepository;
use App\Repository\TransferStatsRepository;
use App\Service\PlanService;
use App\Service\TransferFileValidator;
use App\Service\TransferNotifierInterface;
use App\Service\TusUploadServiceInterface;
use App\Storage\StorageManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ZipArchive;

final readonly class TransferManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TusUploadServiceInterface $tusUploadService,
        private TransferNotifierInterface $notifier,
        private TransferFileValidator $fileValidator,
        private RecipientRepository $recipientRepository,
        private TransferRepository $transferRepository,
        private string $transferStoragePath,
        private TransferStatsRepository $transferStatsRepository,
        private PlanService $planService,
        private LoggerInterface $logger,
        private StorageManager $storageManager,
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
            $transfer->setPasswordHash(password_hash($data['password'], PASSWORD_DEFAULT));
        }

        foreach ($data['recipients'] ?? [] as $email) {
            $recipient = new Recipient();
            $recipient->setEmail($email);
            $transfer->addRecipient($recipient);
        }

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        $this->logger->info('Transfer created', [
            'reference' => $transfer->getReference(),
            'isPublic' => $transfer->isPublic(),
            'recipients' => count($data['recipients'] ?? []),
        ]);

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
        $user = $transfer->getUser();
        $maxFiles = $user instanceof User ? $this->planService->getMaxFiles($user) : $this->planService->getFreeMaxFiles();
        $maxSizeMb = $user instanceof User ? $this->planService->getMaxSizeMb($user) : $this->planService->getFreeMaxSizeMb();

        $this->fileValidator->validate($uploadKeys, $maxFiles, $maxSizeMb);

        if ($user instanceof User && $user->isDemo()) {
            $newTransferSize = 0;
            foreach ($uploadKeys as $key) {
                $upload = $this->tusUploadService->getUpload((string) $key);
                $newTransferSize += (int) ($upload['file_size'] ?? 0);
            }
            $usedBytes = $this->transferRepository->getTotalFilesSizeByUser($user);
            if ($usedBytes + $newTransferSize > PlanService::DEMO_MAX_TOTAL_SIZE_MB * 1024 * 1024) {
                throw new SizeLimitExceededException(PlanService::DEMO_MAX_TOTAL_SIZE_MB);
            }
        }

        $activeBackend = $this->storageManager->getActiveBackend();
        $activeAdapter = $this->storageManager->getActiveAdapter();

        foreach ($uploadKeys as $uploadKey) {
            $upload = $this->tusUploadService->getUpload($uploadKey);

            if (null === $upload) {
                continue;
            }

            $filename = sprintf('%s_%s', bin2hex(random_bytes(8)), basename((string) $upload['file_path']));
            $storageKey = $this->storageManager->buildStorageKey($transfer, $filename);

            $activeAdapter->store($upload['file_path'], $storageKey);

            $file = new TransferFile();
            $file->setOriginalName($upload['original_name']);
            $file->setFilename($filename);
            $file->setMimeType($upload['mime_type']);
            $file->setFileSize($upload['file_size']);
            $file->setStorageBackend($activeBackend);

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
        // Delete each file via its own storage adapter
        foreach ($transfer->getFiles() as $file) {
            $storageKey = $this->storageManager->buildStorageKey($transfer, $file->getFilename());
            $this->storageManager->getAdapterForFile($file)->delete($storageKey);
        }

        // Clean up local directory (also handles orphan files not tracked in DB)
        $storageDir = sprintf('%s/%s', $this->transferStoragePath, $transfer->getToken());
        if (is_dir($storageDir)) {
            foreach (glob(sprintf('%s/*', $storageDir)) ?: [] as $filePath) {
                @unlink($filePath);
            }

            rmdir($storageDir);
        }

        $this->tusUploadService->deleteUploadsByTransferToken($transfer->getToken());

        $filesCount = $transfer->getFiles()->count();
        $filesSize = $transfer->getTotalFilesSize();
        $recipientsCount = $transfer->getRecipients()->count();

        $this->entityManager->remove($transfer);
        $this->entityManager->flush();

        $this->logger->info('Transfer deleted', [
            'reference' => $transfer->getReference(),
            'filesCount' => $filesCount,
        ]);

        $this->transferStatsRepository->increment(
            transfers: 1,
            files: $filesCount,
            filesSize: $filesSize,
            recipients: $recipientsCount,
        );
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

        $r2TmpFiles = [];

        foreach ($transfer->getFiles() as $file) {
            $storageKey = $this->storageManager->buildStorageKey($transfer, $file->getFilename());
            $adapter = $this->storageManager->getAdapterForFile($file);
            $localPath = $adapter->getLocalPath($storageKey);

            if (file_exists($localPath)) {
                $zip->addFile($localPath, $file->getOriginalName());
            }

            if (StorageBackendEnum::R2 === $file->getStorageBackend()) {
                $r2TmpFiles[] = $localPath;
            }
        }

        $zip->close();

        foreach ($r2TmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }

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

        $this->logger->info('Public transfer downloaded', [
            'reference' => $transfer->getReference(),
            'downloadCount' => $transfer->getPublicDownloadCount(),
        ]);
    }

    public function verifyPassword(Transfer $transfer, string $plainPassword): bool
    {
        return password_verify($plainPassword, (string) $transfer->getPasswordHash());
    }
}
