<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\DisallowedFileTypeException;
use App\Exception\FileLimitExceededException;
use App\Exception\SizeLimitExceededException;
use App\Service\TransferFileValidator;
use App\Service\TusUploadServiceInterface;
use PHPUnit\Framework\TestCase;

final class TransferFileValidatorTest extends TestCase
{
    public function testThrowsFileLimitExceededWhenTooManyFiles(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $validator = new TransferFileValidator($tusService);

        $this->expectException(FileLimitExceededException::class);
        $validator->validate(['a', 'b', 'c', 'd'], 3, 100);
    }

    public function testExactlyAtFileLimitDoesNotThrow(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn(null);

        $validator = new TransferFileValidator($tusService);
        $validator->validate(['a', 'b', 'c'], 3, 100);

        $this->addToAssertionCount(1);
    }

    public function testSkipsNullUploads(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn(null);

        $validator = new TransferFileValidator($tusService);
        $validator->validate(['key1'], 10, 100);

        $this->addToAssertionCount(1);
    }

    public function testThrowsDisallowedFileTypeForBlockedExtension(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn([
            'file_size' => 1024,
            'original_name' => 'virus.exe',
            'mime_type' => 'application/octet-stream',
            'file_path' => '/tmp/file',
        ]);

        $validator = new TransferFileValidator($tusService);

        $this->expectException(DisallowedFileTypeException::class);
        $validator->validate(['key1'], 10, 100);
    }

    public function testThrowsDisallowedFileTypeForMismatchedMime(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn([
            'file_size' => 1024,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/x-executable',
            'file_path' => '/tmp/file',
        ]);

        $validator = new TransferFileValidator($tusService);

        $this->expectException(DisallowedFileTypeException::class);
        $validator->validate(['key1'], 10, 100);
    }

    public function testThrowsSizeLimitExceededWhenTotalTooLarge(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn([
            'file_size' => 200 * 1024 * 1024,
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_path' => '/tmp/file',
        ]);

        $validator = new TransferFileValidator($tusService);

        $this->expectException(SizeLimitExceededException::class);
        $validator->validate(['key1'], 10, 100);
    }

    public function testAcceptsValidJpegFile(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn([
            'file_size' => 1 * 1024 * 1024,
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_path' => '/tmp/file',
        ]);

        $validator = new TransferFileValidator($tusService);
        $validator->validate(['key1'], 10, 100);

        $this->addToAssertionCount(1);
    }

    public function testAcceptsValidPdfFile(): void
    {
        $tusService = $this->createStub(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturn([
            'file_size' => 5 * 1024 * 1024,
            'original_name' => 'report.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => '/tmp/file',
        ]);

        $validator = new TransferFileValidator($tusService);
        $validator->validate(['key1'], 10, 100);

        $this->addToAssertionCount(1);
    }

    public function testTotalSizeAcrossMultipleFilesIsChecked(): void
    {
        $calls = 0;
        $tusService = $this->createMock(TusUploadServiceInterface::class);
        $tusService->method('getUpload')->willReturnCallback(function () use (&$calls): array {
            ++$calls;

            return [
                'file_size' => 60 * 1024 * 1024,
                'original_name' => 'photo.jpg',
                'mime_type' => 'image/jpeg',
                'file_path' => '/tmp/file',
            ];
        });

        $validator = new TransferFileValidator($tusService);

        $this->expectException(SizeLimitExceededException::class);
        $validator->validate(['key1', 'key2'], 10, 100); // 60 + 60 = 120 MB > 100 MB limit
    }
}
