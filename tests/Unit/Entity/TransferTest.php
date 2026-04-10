<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Enum\TransferStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TransferTest extends TestCase
{
    public function testConstructorGeneratesTokens(): void
    {
        $transfer = new Transfer();

        self::assertNotEmpty($transfer->getToken());
        self::assertNotEmpty($transfer->getOwnerToken());
        self::assertNotSame($transfer->getToken(), $transfer->getOwnerToken());
        self::assertSame(64, mb_strlen($transfer->getToken()));
        self::assertSame(64, mb_strlen($transfer->getOwnerToken()));
    }

    public function testConstructorGeneratesReference(): void
    {
        $transfer = new Transfer();

        self::assertMatchesRegularExpression('/^[0-9A-F]{4}-[0-9A-F]{4}$/', $transfer->getReference());
    }

    public function testConstructorSetsDefaultExpiry(): void
    {
        $before = new DateTimeImmutable('+6 days 23 hours');
        $transfer = new Transfer();
        $after = new DateTimeImmutable('+7 days 1 minute');

        self::assertGreaterThan($before, $transfer->getExpiresAt());
        self::assertLessThan($after, $transfer->getExpiresAt());
    }

    public function testConstructorSetsPendingStatus(): void
    {
        $transfer = new Transfer();

        self::assertSame(TransferStatusEnum::Pending, $transfer->getStatus());
        self::assertTrue($transfer->isPending());
        self::assertFalse($transfer->isReady());
    }

    public function testIsExpired(): void
    {
        $transfer = new Transfer();
        $transfer->setExpiresAt(new DateTimeImmutable('-1 second'));

        self::assertTrue($transfer->isExpired());
    }

    public function testIsNotExpired(): void
    {
        $transfer = new Transfer();

        self::assertFalse($transfer->isExpired());
    }

    public function testIsPasswordProtected(): void
    {
        $transfer = new Transfer();
        self::assertFalse($transfer->isPasswordProtected());

        $transfer->setPasswordHash(password_hash('secret', PASSWORD_BCRYPT));
        self::assertTrue($transfer->isPasswordProtected());
    }

    public function testAddFile(): void
    {
        $transfer = new Transfer();
        $file = new TransferFile();
        $file->setOriginalName('test.pdf');
        $file->setFilename('abc_test.pdf');
        $file->setFileSize(1024);

        $transfer->addFile($file);

        self::assertCount(1, $transfer->getFiles());
        self::assertSame($transfer, $file->getTransfer());
    }

    public function testAddFilePreventssDuplicates(): void
    {
        $transfer = new Transfer();
        $file = new TransferFile();

        $transfer->addFile($file);
        $transfer->addFile($file);

        self::assertCount(1, $transfer->getFiles());
    }

    public function testAddRecipient(): void
    {
        $transfer = new Transfer();
        $recipient = new Recipient();
        $recipient->setEmail('test@example.com');

        $transfer->addRecipient($recipient);

        self::assertCount(1, $transfer->getRecipients());
        self::assertTrue($transfer->hasRecipients());
    }

    public function testGetTotalFilesSize(): void
    {
        $transfer = new Transfer();

        $file1 = new TransferFile();
        $file1->setFileSize(1024);

        $file2 = new TransferFile();
        $file2->setFileSize(2048);

        $transfer->addFile($file1);
        $transfer->addFile($file2);

        self::assertSame(3072, $transfer->getTotalFilesSize());
    }

    public function testSetSenderEmailNormalizesInput(): void
    {
        $transfer = new Transfer();
        $transfer->setSenderEmail('  Test@EXAMPLE.COM  ');

        self::assertSame('test@example.com', $transfer->getSenderEmail());
    }

    public function testSetSenderEmailHandlesEmpty(): void
    {
        $transfer = new Transfer();
        $transfer->setSenderEmail('');

        self::assertNull($transfer->getSenderEmail());
    }
}
