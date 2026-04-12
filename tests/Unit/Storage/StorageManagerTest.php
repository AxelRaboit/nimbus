<?php

declare(strict_types=1);

namespace App\Tests\Unit\Storage;

use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\StorageBackendEnum;
use App\Repository\ApplicationParameterRepository;
use App\Storage\StorageAdapterInterface;
use App\Storage\StorageManager;
use PHPUnit\Framework\TestCase;

final class StorageManagerTest extends TestCase
{
    public function testBuildStorageKeyFormatsCorrectly(): void
    {
        $transfer = new Transfer();
        $manager = $this->buildManager('local');

        self::assertSame(
            sprintf('%s/abc_file.pdf', $transfer->getToken()),
            $manager->buildStorageKey($transfer, 'abc_file.pdf'),
        );
    }

    public function testGetActiveBackendDefaultsToLocalWhenParamIsLocal(): void
    {
        self::assertSame(StorageBackendEnum::Local, $this->buildManager('local')->getActiveBackend());
    }

    public function testGetActiveBackendReturnsR2WhenConfigured(): void
    {
        self::assertSame(StorageBackendEnum::R2, $this->buildManager('r2')->getActiveBackend());
    }

    public function testGetActiveBackendFallsBackToLocalForUnknownValue(): void
    {
        self::assertSame(StorageBackendEnum::Local, $this->buildManager('ftp')->getActiveBackend());
    }

    public function testGetAdapterForFileReturnsLocalAdapterForLocalFile(): void
    {
        $local = $this->createStub(StorageAdapterInterface::class);
        $r2 = $this->createStub(StorageAdapterInterface::class);
        $manager = new StorageManager($local, $r2, $this->stubParams('local'));

        $file = new TransferFile();
        $file->setStorageBackend(StorageBackendEnum::Local);

        self::assertSame($local, $manager->getAdapterForFile($file));
    }

    public function testGetAdapterForFileReturnsR2AdapterForR2File(): void
    {
        $local = $this->createStub(StorageAdapterInterface::class);
        $r2 = $this->createStub(StorageAdapterInterface::class);
        $manager = new StorageManager($local, $r2, $this->stubParams('r2'));

        $file = new TransferFile();
        $file->setStorageBackend(StorageBackendEnum::R2);

        self::assertSame($r2, $manager->getAdapterForFile($file));
    }

    public function testGetActiveAdapterReturnsLocalWhenBackendIsLocal(): void
    {
        $local = $this->createStub(StorageAdapterInterface::class);
        $r2 = $this->createStub(StorageAdapterInterface::class);
        $manager = new StorageManager($local, $r2, $this->stubParams('local'));

        self::assertSame($local, $manager->getActiveAdapter());
    }

    public function testFileExistsDelegatesToCorrectAdapterWithCorrectKey(): void
    {
        $transfer = new Transfer();

        $file = new TransferFile();
        $file->setFilename('abc_doc.pdf');
        $file->setStorageBackend(StorageBackendEnum::Local);

        $capturedKey = null;
        $local = $this->createStub(StorageAdapterInterface::class);
        $local->method('exists')->willReturnCallback(function (string $key) use (&$capturedKey): bool {
            $capturedKey = $key;

            return true;
        });

        $r2 = $this->createStub(StorageAdapterInterface::class);
        $manager = new StorageManager($local, $r2, $this->stubParams('local'));

        self::assertTrue($manager->fileExists($transfer, $file));
        self::assertSame(sprintf('%s/abc_doc.pdf', $transfer->getToken()), $capturedKey);
    }

    private function buildManager(string $backendValue): StorageManager
    {
        return new StorageManager(
            $this->createStub(StorageAdapterInterface::class),
            $this->createStub(StorageAdapterInterface::class),
            $this->stubParams($backendValue),
        );
    }

    private function stubParams(string $backendValue): ApplicationParameterRepository
    {
        $params = $this->createStub(ApplicationParameterRepository::class);
        $params->method('get')->willReturn($backendValue);

        return $params;
    }
}
