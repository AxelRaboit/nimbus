<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\AccessRequest;
use App\Enum\AccessRequestStatusEnum;
use App\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class AccessRequestControllerTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('ACCESS_PASSWORD=');
        $_ENV['ACCESS_PASSWORD'] = '';
        $_SERVER['ACCESS_PASSWORD'] = '';
        static::ensureKernelShutdown();
    }

    // ── /approve ─────────────────────────────────────────────────────────────

    public function testApproveWithValidTokenRendersSuccess(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createPendingRequest($entityManager);

        $client->request('GET', '/access-request/'.$accessRequest->getToken().'/approve');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Accès autorisé', $client->getResponse()->getContent());
    }

    public function testApproveWithValidTokenSetsApprovedStatus(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createPendingRequest($entityManager);
        $token = $accessRequest->getToken();

        $client->request('GET', '/access-request/'.$token.'/approve');

        self::assertResponseIsSuccessful();

        $entityManager->clear();
        $approved = $entityManager->getRepository(AccessRequest::class)->findOneBy(['token' => $token]);
        self::assertTrue($approved->isApproved());
        self::assertNotNull($approved->getAccessToken());
    }

    public function testApproveWithUnknownTokenRendersInvalid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access-request/'.str_repeat('a', 64).'/approve');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lien invalide', $client->getResponse()->getContent());
    }

    public function testApproveWithExpiredTokenRendersExpired(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createPendingRequest($entityManager, expiresAt: new DateTimeImmutable('-1 hour'));

        $client->request('GET', '/access-request/'.$accessRequest->getToken().'/approve');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lien expiré', $client->getResponse()->getContent());
    }

    public function testApproveAlreadyApprovedRendersInvalid(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createPendingRequest($entityManager);
        $accessRequest->setStatus(AccessRequestStatusEnum::Approved);
        $entityManager->flush();

        $client->request('GET', '/access-request/'.$accessRequest->getToken().'/approve');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lien invalide', $client->getResponse()->getContent());
    }

    // ── /grant ───────────────────────────────────────────────────────────────

    public function testGrantWithValidTokenRedirectsToHome(): void
    {
        $this->setAccessPassword('secret');

        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createApprovedRequest($entityManager);

        $client->request('GET', '/access-request/'.$accessRequest->getAccessToken().'/grant');

        self::assertResponseRedirects('/');
    }

    public function testGrantInvalidatesTokenAfterUse(): void
    {
        $this->setAccessPassword('secret');

        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createApprovedRequest($entityManager);
        $accessToken = $accessRequest->getAccessToken();
        $entityManagerail = $accessRequest->getRequesterEmail();

        $client->request('GET', '/access-request/'.$accessToken.'/grant');
        self::assertResponseRedirects('/');

        $entityManager->clear();
        $used = $entityManager->getRepository(AccessRequest::class)->findOneBy(['requesterEmail' => $entityManagerail]);
        self::assertNull($used->getAccessToken());
    }

    public function testGrantSecondTimeRendersInvalidAfterTokenNulled(): void
    {
        $this->setAccessPassword('secret');

        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createApprovedRequest($entityManager);
        $accessToken = $accessRequest->getAccessToken();

        $client->request('GET', '/access-request/'.$accessToken.'/grant');
        self::assertResponseRedirects('/');

        $client->request('GET', '/access-request/'.$accessToken.'/grant');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lien invalide', $client->getResponse()->getContent());
    }

    public function testGrantWithExpiredAccessTokenRendersExpired(): void
    {
        $this->setAccessPassword('secret');

        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createApprovedRequest($entityManager, accessTokenExpiresAt: new DateTimeImmutable('-1 hour'));

        $client->request('GET', '/access-request/'.$accessRequest->getAccessToken().'/grant');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lien expiré', $client->getResponse()->getContent());
    }

    public function testGrantWithUnknownTokenRendersInvalid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access-request/'.str_repeat('b', 64).'/grant');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lien invalide', $client->getResponse()->getContent());
    }

    public function testGrantStoresGrantedFileSizeMbInSession(): void
    {
        $this->setAccessPassword('secret');

        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $accessRequest = $this->createApprovedRequest($entityManager);
        $accessRequest->setGrantedFileSizeMb(500);
        $entityManager->flush();

        $client->request('GET', '/access-request/'.$accessRequest->getAccessToken().'/grant');

        self::assertResponseRedirects('/');
        self::assertSame(500, $client->getRequest()->getSession()->get('custom_file_size_mb'));
    }

    public function testGrantWithoutGrantedFileSizeMbDoesNotSetSessionKey(): void
    {
        $this->setAccessPassword('secret');

        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $accessRequest = $this->createApprovedRequest($entityManager);

        $client->request('GET', '/access-request/'.$accessRequest->getAccessToken().'/grant');

        self::assertResponseRedirects('/');
        self::assertNull($client->getRequest()->getSession()->get('custom_file_size_mb'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createPendingRequest(
        EntityManagerInterface $entityManager,
        string $entityManagerail = 'requester@example.com',
        ?DateTimeImmutable $expiresAt = null,
    ): AccessRequest {
        $request = new AccessRequest($entityManagerail, $expiresAt ?? new DateTimeImmutable('+24 hours'));
        $entityManager->persist($request);
        $entityManager->flush();

        return $request;
    }

    private function createApprovedRequest(
        EntityManagerInterface $entityManager,
        string $entityManagerail = 'approved@example.com',
        ?DateTimeImmutable $accessTokenExpiresAt = null,
    ): AccessRequest {
        $request = new AccessRequest($entityManagerail, new DateTimeImmutable('+24 hours'));
        $request->setStatus(AccessRequestStatusEnum::Approved);
        $request->setAccessToken(bin2hex(random_bytes(32)));
        $request->setAccessTokenExpiresAt($accessTokenExpiresAt ?? new DateTimeImmutable('+24 hours'));
        $entityManager->persist($request);
        $entityManager->flush();

        return $request;
    }

    private function setAccessPassword(string $password): void
    {
        static::ensureKernelShutdown();
        putenv("ACCESS_PASSWORD={$password}");
        $_ENV['ACCESS_PASSWORD'] = $password;
        $_SERVER['ACCESS_PASSWORD'] = $password;
    }
}
