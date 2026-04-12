<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AccessRequestTest extends TestCase
{
    public function testConstructorGeneratesToken(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        self::assertNotEmpty($request->getToken());
        self::assertSame(64, mb_strlen($request->getToken()));
    }

    public function testConstructorGeneratesUniqueTokens(): void
    {
        $a = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));
        $b = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        self::assertNotSame($a->getToken(), $b->getToken());
    }

    public function testConstructorSetsPendingStatus(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        self::assertSame(AccessRequestStatusEnum::Pending, $request->getStatus());
        self::assertTrue($request->isPending());
        self::assertFalse($request->isApproved());
    }

    public function testConstructorSetsNullAccessToken(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        self::assertNull($request->getAccessToken());
    }

    public function testIsAdminLinkExpiredWhenExpired(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('-1 second'));

        self::assertTrue($request->isAdminLinkExpired());
    }

    public function testIsAdminLinkExpiredWhenNotExpired(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        self::assertFalse($request->isAdminLinkExpired());
    }

    public function testIsAccessTokenExpiredWhenNull(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));

        self::assertTrue($request->isAccessTokenExpired());
    }

    public function testIsAccessTokenExpiredWhenPast(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));
        $request->setAccessTokenExpiresAt(new DateTimeImmutable('-1 second'));

        self::assertTrue($request->isAccessTokenExpired());
    }

    public function testIsAccessTokenExpiredWhenFuture(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));
        $request->setAccessTokenExpiresAt(new DateTimeImmutable('+24 hours'));

        self::assertFalse($request->isAccessTokenExpired());
    }

    public function testIsPendingAfterStatusChange(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));
        $request->setStatus(AccessRequestStatusEnum::Approved);

        self::assertFalse($request->isPending());
        self::assertTrue($request->isApproved());
    }

    public function testSetAccessTokenNullInvalidatesToken(): void
    {
        $request = new AccessRequest('user@example.com', new DateTimeImmutable('+24 hours'));
        $request->setAccessToken(bin2hex(random_bytes(32)));

        self::assertNotNull($request->getAccessToken());

        $request->setAccessToken(null);

        self::assertNull($request->getAccessToken());
    }
}
