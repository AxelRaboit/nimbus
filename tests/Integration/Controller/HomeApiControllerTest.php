<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Enum\ContentTypeEnum;
use App\Enum\HttpMethodEnum;
use App\Tests\Integration\IntegrationTestCase;

final class HomeApiControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::createClient();
        static::getContainer()->get('cache.rate_limiter')->clear();
        static::ensureKernelShutdown();
    }

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

    // ── POST /api/home/request-access ────────────────────────────────────────

    public function testRequestAccessReturns400WhenNoPasswordEnabled(): void
    {
        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/request-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['email' => 'user@example.com']));

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'access_password_not_enabled'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testRequestAccessReturns422WithInvalidEmail(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/request-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['email' => 'not-an-email']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['error' => 'invalid_email'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testRequestAccessReturns422WithEmptyEmail(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/request-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['email' => '']));

        self::assertResponseStatusCodeSame(422);
    }

    public function testRequestAccessReturns400WithInvalidPayload(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/request-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], 'not-json');

        self::assertResponseStatusCodeSame(400);
    }

    public function testRequestAccessReturns201OnSuccess(): void
    {
        $this->setAccessPassword('testpass');

        $client = static::createClient();
        $client->request(HttpMethodEnum::Post->value, '/api/home/request-access', [], [], ['CONTENT_TYPE' => ContentTypeEnum::Json->value], json_encode(['email' => 'newuser@example.com', 'name' => 'New User', 'message' => 'Please let me in']));

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    private function setAccessPassword(string $password): void
    {
        static::ensureKernelShutdown();
        putenv("ACCESS_PASSWORD={$password}");
        $_ENV['ACCESS_PASSWORD'] = $password;
        $_SERVER['ACCESS_PASSWORD'] = $password;
    }
}
