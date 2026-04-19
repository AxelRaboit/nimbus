<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\EmailValidator;
use PHPUnit\Framework\TestCase;

final class EmailValidatorTest extends TestCase
{
    public function testValidEmailReturnsTrue(): void
    {
        self::assertTrue(EmailValidator::isValid('user@example.com'));
    }

    public function testInvalidEmailReturnsFalse(): void
    {
        self::assertFalse(EmailValidator::isValid('not-an-email'));
    }

    public function testEmptyStringReturnsFalse(): void
    {
        self::assertFalse(EmailValidator::isValid(''));
    }

    public function testEmailWithoutDomainReturnsFalse(): void
    {
        self::assertFalse(EmailValidator::isValid('user@'));
    }

    public function testEmailWithoutAtSignReturnsFalse(): void
    {
        self::assertFalse(EmailValidator::isValid('userexample.com'));
    }

    public function testEmailWithSubdomainReturnsTrue(): void
    {
        self::assertTrue(EmailValidator::isValid('user@mail.example.co.uk'));
    }

    public function testEmailWithPlusAliasReturnsTrue(): void
    {
        self::assertTrue(EmailValidator::isValid('user+tag@example.com'));
    }
}
