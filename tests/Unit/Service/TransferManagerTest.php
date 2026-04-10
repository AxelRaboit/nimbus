<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Transfer;
use App\Enum\TransferStatusEnum;
use App\Service\TransferManager;
use App\Service\TransferNotifierInterface;
use App\Service\TusUploadServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class TransferManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private TusUploadServiceInterface&MockObject $tusService;
    private TransferNotifierInterface&Stub $notifier;
    private string $storagePath;
    private TransferManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tusService = $this->createMock(TusUploadServiceInterface::class);
        $this->notifier = $this->createStub(TransferNotifierInterface::class);
        $this->storagePath = sys_get_temp_dir().'/nimbus_test_'.uniqid();

        $this->manager = new TransferManager(
            $this->em,
            $this->tusService,
            $this->notifier,
            $this->storagePath,
        );
    }

    public function testFinalizeSkipsUnknownUploadKeys(): void
    {
        $transfer = new Transfer();

        $this->tusService
            ->expects(self::once())
            ->method('getUpload')
            ->with('unknown_key')
            ->willReturn(null);

        $this->em->expects(self::once())->method('flush');

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReady');
        $this->replaceNotifier($notifier);

        $this->manager->finalize($transfer, ['unknown_key']);

        self::assertSame(TransferStatusEnum::Ready, $transfer->getStatus());
        self::assertCount(0, $transfer->getFiles());
    }

    public function testFinalizeMovesFileAndCreatesEntity(): void
    {
        $transfer = new Transfer();

        $tmpFile = tempnam(sys_get_temp_dir(), 'tus_');
        file_put_contents($tmpFile, 'test content');

        $this->tusService
            ->expects(self::once())
            ->method('getUpload')
            ->with('upload_key_1')
            ->willReturn([
                'file_path' => $tmpFile,
                'original_name' => 'document.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 12,
            ]);

        $this->tusService->expects(self::once())->method('deleteUpload');
        $this->em->expects(self::once())->method('flush');

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReady')->with($transfer);
        $this->replaceNotifier($notifier);

        $this->manager->finalize($transfer, ['upload_key_1']);

        self::assertSame(TransferStatusEnum::Ready, $transfer->getStatus());
        self::assertCount(1, $transfer->getFiles());
        self::assertSame('document.pdf', $transfer->getFiles()->first()->getOriginalName());

        // Cleanup
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

        $this->tusService->expects(self::once())->method('deleteUploadsByTransferToken');
        $this->em->expects(self::once())->method('remove')->with($transfer);
        $this->em->expects(self::once())->method('flush');

        $this->manager->delete($transfer);

        self::assertDirectoryDoesNotExist($storageDir);
    }

    private function replaceNotifier(TransferNotifierInterface $notifier): void
    {
        $this->manager = new TransferManager(
            $this->em,
            $this->tusService,
            $notifier,
            $this->storagePath,
        );
    }
}
