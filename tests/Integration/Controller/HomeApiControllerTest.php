<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Enum\ContentTypeEnum;
use App\Enum\HttpMethodEnum;
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
        $client->request(HttpMethodEnum::Post->value, '/api/home/verify-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['password' => '']));

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testVerifyAccessWithCorrectPasswordReturnsOk(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/verify-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['password' => 'testpass']));

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testVerifyAccessWithWrongPasswordReturnsForbidden(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/verify-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['password' => 'wrongpass']));

        self::assertResponseStatusCodeSame(403);
    }

    public function testHomePageShowsAccessPasswordEnabledWhenSet(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Get->value, '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-access-password-enabled="true"', $client->getResponse()->getContent());
    }

    public function testHomePageShowsAccessNotGrantedInitially(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Get->value, '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-access-granted="false"', $client->getResponse()->getContent());
    }

    public function testAfterVerifyAccessHomePageShowsAccessGranted(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();

        $client->request(HttpMethodEnum::Post->value, '/api/home/verify-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['password' => 'testpass']));
        self::assertResponseIsSuccessful();

        $client->request(HttpMethodEnum::Get->value, '/');
        self::assertStringContainsString('data-access-granted="true"', $client->getResponse()->getContent());
    }

    public function testHomePageShowsNoAccessPasswordWhenNotSet(): void
    {
        $client = static::createClient();
        $client->request(HttpMethodEnum::Get->value, '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-access-password-enabled="false"', $client->getResponse()->getContent());
        self::assertStringContainsString('data-access-granted="true"', $client->getResponse()->getContent());
    }

    public function testStaleSessionHashDoesNotGrantAccessAfterPasswordChange(): void
    {
        $this->setAccessPassword('newpass');
        $client = static::createClient();

        $client->request(HttpMethodEnum::Get->value, '/');
        $client->getRequest()->getSession()->set('access_granted_hash', md5('oldpass'));
        $client->getRequest()->getSession()->save();

        $client->request(HttpMethodEnum::Get->value, '/');
        self::assertStringContainsString('data-access-granted="false"', $client->getResponse()->getContent());
    }

    public function testCorrectPasswordStoresHashInSession(): void
    {
        $this->setAccessPassword('secret');
        $client = static::createClient();

        $client->request(HttpMethodEnum::Post->value, '/api/home/verify-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['password' => 'secret']));
        self::assertResponseIsSuccessful();

        $client->request(HttpMethodEnum::Get->value, '/');
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
