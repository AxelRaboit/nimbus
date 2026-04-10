<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\IntegrationTestCase;

final class HomeApiControllerTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('ACCESS_PASSWORD=');
        $_ENV['ACCESS_PASSWORD'] = '';
        $_SERVER['ACCESS_PASSWORD'] = '';
        static::ensureKernelShutdown();
    }

    public function testVerifyAccessWhenNoPasswordSetAlwaysSucceeds(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/home/verify-access', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['password' => '']));

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testVerifyAccessWithCorrectPasswordReturnsOk(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request('POST', '/api/home/verify-access', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['password' => 'testpass']));

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testVerifyAccessWithWrongPasswordReturnsForbidden(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request('POST', '/api/home/verify-access', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['password' => 'wrongpass']));

        self::assertResponseStatusCodeSame(403);
    }

    public function testHomePageShowsAccessPasswordEnabledWhenSet(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-access-password-enabled="true"', $client->getResponse()->getContent());
    }

    public function testHomePageShowsAccessNotGrantedInitially(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-access-granted="false"', $client->getResponse()->getContent());
    }

    public function testAfterVerifyAccessHomePageShowsAccessGranted(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();

        $client->request('POST', '/api/home/verify-access', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['password' => 'testpass']));
        self::assertResponseIsSuccessful();

        $client->request('GET', '/');
        self::assertStringContainsString('data-access-granted="true"', $client->getResponse()->getContent());
    }

    public function testHomePageShowsNoAccessPasswordWhenNotSet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-access-password-enabled="false"', $client->getResponse()->getContent());
        self::assertStringContainsString('data-access-granted="true"', $client->getResponse()->getContent());
    }

    private function setAccessPassword(string $password): void
    {
        static::ensureKernelShutdown();
        putenv("ACCESS_PASSWORD={$password}");
        $_ENV['ACCESS_PASSWORD'] = $password;
        $_SERVER['ACCESS_PASSWORD'] = $password;
    }
}
