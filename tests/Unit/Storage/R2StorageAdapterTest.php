<?php

declare(strict_types=1);

namespace App\Tests\Unit\Storage;

use App\Storage\R2StorageAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class R2StorageAdapterTest extends TestCase
{
    public function testThrowsWhenAllCredentialsAreNull(): void
    {
        $adapter = new R2StorageAdapter(null, null, null, 'bucket');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessageMatches('/not configured/');

        $adapter->exists('some/key');
    }

    public function testThrowsWhenEndpointIsEmpty(): void
    {
        $adapter = new R2StorageAdapter('', 'key-id', 'secret', 'bucket');

        self::expectException(RuntimeException::class);

        $adapter->exists('some/key');
    }

    public function testThrowsWhenAccessKeyIdIsEmpty(): void
    {
        $adapter = new R2StorageAdapter('https://account.r2.cloudflarestorage.com', '', 'secret', 'bucket');

        self::expectException(RuntimeException::class);

        $adapter->exists('some/key');
    }

    public function testThrowsWhenSecretIsEmpty(): void
    {
        $adapter = new R2StorageAdapter('https://account.r2.cloudflarestorage.com', 'key-id', '', 'bucket');

        self::expectException(RuntimeException::class);

        $adapter->exists('some/key');
    }
}
