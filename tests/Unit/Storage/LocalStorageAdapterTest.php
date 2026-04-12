<?php

declare(strict_types=1);

namespace App\Tests\Unit\Storage;

use App\Enum\ContentTypeEnum;
use App\Storage\LocalStorageAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LocalStorageAdapterTest extends TestCase
{
    private string $storagePath;
    private LocalStorageAdapter $adapter;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir().'/nimbus_local_adapter_'.uniqid();
        mkdir($this->storagePath, 0o750, true);
        $this->adapter = new LocalStorageAdapter($this->storagePath);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->storagePath.'/*/*') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($this->storagePath.'/*') ?: [] as $dir) {
            @rmdir($dir);
        }
        @rmdir($this->storagePath);
    }

    public function testStoreMovesFileToStoragePath(): void
    {
        $src = (string) tempnam(sys_get_temp_dir(), 'tus_');
        file_put_contents($src, 'hello');

        $this->adapter->store($src, 'token123/myfile.txt');

        self::assertFileDoesNotExist($src);
        self::assertFileExists($this->storagePath.'/token123/myfile.txt');
        self::assertSame('hello', file_get_contents($this->storagePath.'/token123/myfile.txt'));
    }

    public function testStoreCreatesDirectoryIfMissing(): void
    {
        $src = (string) tempnam(sys_get_temp_dir(), 'tus_');
        file_put_contents($src, 'data');

        $this->adapter->store($src, 'newtoken/file.txt');

        self::assertDirectoryExists($this->storagePath.'/newtoken');
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        mkdir($this->storagePath.'/token', 0o750, true);
        file_put_contents($this->storagePath.'/token/file.txt', 'x');

        self::assertTrue($this->adapter->exists('token/file.txt'));
    }

    public function testExistsReturnsFalseForMissingFile(): void
    {
        self::assertFalse($this->adapter->exists('notoken/missing.txt'));
    }

    public function testDeleteRemovesFile(): void
    {
        mkdir($this->storagePath.'/token', 0o750, true);
        file_put_contents($this->storagePath.'/token/file.txt', 'x');

        $this->adapter->delete('token/file.txt');

        self::assertFileDoesNotExist($this->storagePath.'/token/file.txt');
    }

    public function testDeleteDoesNothingForMissingFile(): void
    {
        $this->adapter->delete('notoken/missing.txt');
        self::assertTrue(true);
    }

    public function testGetLocalPathReturnsFullPath(): void
    {
        self::assertSame(
            $this->storagePath.'/token/file.txt',
            $this->adapter->getLocalPath('token/file.txt'),
        );
    }

    public function testCreateDownloadResponseReturnsBinaryFileResponse(): void
    {
        mkdir($this->storagePath.'/token', 0o750, true);
        file_put_contents($this->storagePath.'/token/file.txt', 'content');

        $response = $this->adapter->createDownloadResponse('token/file.txt', 'original.txt', 'text/plain', false);

        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function testCreateInlineResponseUsesInlineDisposition(): void
    {
        mkdir($this->storagePath.'/token', 0o750, true);
        file_put_contents($this->storagePath.'/token/image.jpg', 'imgdata');

        $response = $this->adapter->createDownloadResponse('token/image.jpg', 'image.jpg', 'image/jpeg', true);

        self::assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function testCreateDownloadResponseFallsBackToOctetStreamWhenNoMimeType(): void
    {
        mkdir($this->storagePath.'/token', 0o750, true);
        file_put_contents($this->storagePath.'/token/file.bin', 'data');

        $response = $this->adapter->createDownloadResponse('token/file.bin', 'file.bin', null, false);

        self::assertSame(ContentTypeEnum::OctetStream->value, $response->headers->get('Content-Type'));
    }
}
