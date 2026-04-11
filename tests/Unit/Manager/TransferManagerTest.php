<?php

declare(strict_types=1);

namespace App\Tests\Unit\Manager;

use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\TransferStatusEnum;
use App\Manager\TransferManager;
use App\Repository\ApplicationParameterRepository;
use App\Repository\RecipientRepository;
use App\Service\TransferFileValidator;
use App\Service\TransferNotifierInterface;
use App\Service\TusUploadServiceInterface;
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
            $this->storagePath.'/'.$transfer->getToken().'/abc_doc.pdf',
            $this->buildManager()->resolveFilePath($transfer, $file),
        );
    }

    public function testFinalizeSkipsUnknownUploadKeys(): void
    {
        $transfer = new Transfer();

        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn(null);

        $paramRepo = $this->createStub(ApplicationParameterRepository::class);
        $paramRepo->method('get')->willReturnMap([
            ['max_files_per_transfer_pro', null, '20'],
            ['max_transfer_size_mb_pro', null, '10000'],
        ]);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReady');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->buildManager(em: $em, tusService: $tusService, notifier: $notifier, paramRepo: $paramRepo)
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

        $paramRepo = $this->createStub(ApplicationParameterRepository::class);
        $paramRepo->method('get')->willReturnMap([
            ['max_files_per_transfer_pro', null, '20'],
            ['max_transfer_size_mb_pro', null, '10000'],
        ]);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReady')->with($transfer);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->buildManager(em: $em, tusService: $tusService, notifier: $notifier, paramRepo: $paramRepo)
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

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($transfer);
        $em->expects(self::once())->method('flush');

        $this->buildManager(em: $em, tusService: $tusService)->delete($transfer);

        self::assertDirectoryDoesNotExist($storageDir);
    }

    private function buildManager(
        ?EntityManagerInterface $em = null,
        ?TusUploadServiceInterface $tusService = null,
        ?TransferNotifierInterface $notifier = null,
        ?ApplicationParameterRepository $paramRepo = null,
    ): TransferManager {
        $tusService ??= $this->createStub(TusUploadServiceInterface::class);

        return new TransferManager(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $tusService,
            $notifier ?? $this->createStub(TransferNotifierInterface::class),
            new TransferFileValidator($tusService),
            $this->createStub(RecipientRepository::class),
            $this->storagePath,
            $paramRepo ?? $this->createStub(ApplicationParameterRepository::class),
            new NullLogger(),
        );
    }
}
