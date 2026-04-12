<?php

declare(strict_types=1);

namespace App\Tests\Unit\Manager;

use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\TransferStatusEnum;
use App\Manager\TransferManager;
use App\Repository\ApplicationParameterRepository;
use App\Repository\RecipientRepository;
use App\Repository\TransferStatsRepository;
use App\Service\PlanService;
use App\Service\TransferFileValidator;
use App\Service\TransferNotifierInterface;
use App\Service\TusUploadServiceInterface;
use App\Storage\LocalStorageAdapter;
use App\Storage\R2StorageAdapter;
use App\Storage\StorageManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TransferManagerTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir().'/nimbus_test_'.uniqid();
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $transfer = new Transfer();
        $transfer->setPasswordHash(password_hash('secret', PASSWORD_DEFAULT));

        self::assertTrue($this->buildManager()->verifyPassword($transfer, 'secret'));
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
        $transfer = new Transfer();
        $transfer->setPasswordHash(password_hash('secret', PASSWORD_DEFAULT));

        self::assertFalse($this->buildManager()->verifyPassword($transfer, 'wrong'));
    }

    public function testFindFileByFilenameReturnsMatchingFile(): void
    {
        $transfer = new Transfer();

        $file = new TransferFile();
        $file->setFilename('abc_doc.pdf');
        $file->setOriginalName('document.pdf');
        $file->setFileSize(1024);
        $transfer->addFile($file);

        self::assertSame($file, $this->buildManager()->findFileByFilename($transfer, 'abc_doc.pdf'));
    }

    public function testFindFileByFilenameReturnsNullWhenNotFound(): void
    {
        $transfer = new Transfer();

        self::assertNull($this->buildManager()->findFileByFilename($transfer, 'missing.pdf'));
    }

    public function testResolveFilePath(): void
    {
        $transfer = new Transfer();

        $file = new TransferFile();
        $file->setFilename('abc_doc.pdf');

        self::assertSame(
            sprintf('%s/%s/abc_doc.pdf', $this->storagePath, $transfer->getToken()),
            $this->buildManager()->resolveFilePath($transfer, $file),
        );
    }

    public function testFinalizeSkipsUnknownUploadKeys(): void
    {
        $transfer = new Transfer();

        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn(null);

        $planService = $this->createStub(PlanService::class);
        $planService->method('getProMaxFiles')->willReturn(20);
        $planService->method('getProMaxSizeMb')->willReturn(10000);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReady');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $this->buildManager(entityManager: $entityManager, tusService: $tusService, notifier: $notifier, planService: $planService)
            ->finalize($transfer, ['unknown_key']);

        self::assertSame(TransferStatusEnum::Ready, $transfer->getStatus());
        self::assertCount(0, $transfer->getFiles());
    }

    public function testFinalizeMovesFileAndCreatesEntity(): void
    {
        $transfer = new Transfer();

        $tmpFile = tempnam(sys_get_temp_dir(), 'tus_');
        file_put_contents($tmpFile, 'test content');

        $uploadData = [
            'file_path' => $tmpFile,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
        ];

        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn($uploadData);

        $planService = $this->createStub(PlanService::class);
        $planService->method('getProMaxFiles')->willReturn(20);
        $planService->method('getProMaxSizeMb')->willReturn(10000);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReady')->with($transfer);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $this->buildManager(entityManager: $entityManager, tusService: $tusService, notifier: $notifier, planService: $planService)
            ->finalize($transfer, ['upload_key_1']);

        self::assertSame(TransferStatusEnum::Ready, $transfer->getStatus());
        self::assertCount(1, $transfer->getFiles());
        self::assertSame('document.pdf', $transfer->getFiles()->first()->getOriginalName());

        $storageDir = $this->storagePath.'/'.$transfer->getToken();
        foreach (glob($storageDir.'/*') ?: [] as $f) {
            unlink($f);
        }
        if (is_dir($storageDir)) {
            rmdir($storageDir);
        }
    }

    public function testDeleteRemovesFilesAndCallsFlush(): void
    {
        $transfer = new Transfer();
        $transfer->setStatus(TransferStatusEnum::Ready);

        $storageDir = $this->storagePath.'/'.$transfer->getToken();
        mkdir($storageDir, 0o750, true);
        file_put_contents($storageDir.'/test_file.txt', 'data');

        $tusService = $this->createMock(TusUploadServiceInterface::class);
        $tusService->expects(self::once())->method('deleteUploadsByTransferToken');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($transfer);
        $entityManager->expects(self::once())->method('flush');

        $this->buildManager(entityManager: $entityManager, tusService: $tusService)->delete($transfer);

        self::assertDirectoryDoesNotExist($storageDir);
    }

    private function buildManager(
        ?EntityManagerInterface $entityManager = null,
        ?TusUploadServiceInterface $tusService = null,
        ?TransferNotifierInterface $notifier = null,
        ?PlanService $planService = null,
    ): TransferManager {
        $tusService ??= $this->createStub(TusUploadServiceInterface::class);

        $paramsRepo = $this->createStub(ApplicationParameterRepository::class);
        $paramsRepo->method('get')->willReturn('local');

        $storageManager = new StorageManager(
            new LocalStorageAdapter($this->storagePath),
            new R2StorageAdapter(null, null, null, 'test'), // credentials not needed for local backend
            $paramsRepo,
        );

        return new TransferManager(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $tusService,
            $notifier ?? $this->createStub(TransferNotifierInterface::class),
            new TransferFileValidator($tusService),
            $this->createStub(RecipientRepository::class),
            $this->storagePath,
            $this->createStub(TransferStatsRepository::class),
            $planService ?? $this->createStub(PlanService::class),
            new NullLogger(),
            $storageManager,
        );
    }
}
