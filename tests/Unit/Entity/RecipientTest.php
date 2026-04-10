<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Recipient;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RecipientTest extends TestCase
{
    public function testConstructorGeneratesToken(): void
    {
        $recipient = new Recipient();

        self::assertNotEmpty($recipient->getToken());
        self::assertSame(64, mb_strlen($recipient->getToken()));
    }

    public function testHasNotDownloadedByDefault(): void
    {
        $recipient = new Recipient();

        self::assertFalse($recipient->hasDownloaded());
        self::assertNull($recipient->getDownloadedAt());
    }

    public function testMarkAsDownloaded(): void
    {
        $recipient = new Recipient();
        $before = new DateTimeImmutable();

        $recipient->markAsDownloaded();

        self::assertTrue($recipient->hasDownloaded());
        self::assertInstanceOf(DateTimeImmutable::class, $recipient->getDownloadedAt());
        self::assertGreaterThanOrEqual($before, $recipient->getDownloadedAt());
    }

    public function testSetEmail(): void
    {
        $recipient = new Recipient();
        $recipient->setEmail('test@example.com');

        self::assertSame('test@example.com', $recipient->getEmail());
    }
}
