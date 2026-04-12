<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Tests\Integration\IntegrationTestCase;

final class TransferApiControllerTest extends IntegrationTestCase
{
    public function testListTransfersRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/transfers');

        self::assertResponseStatusCodeSame(401);
    }

    public function testListTransfersForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/api/transfers');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasMore', $data);
    }

    public function testCreateTransferReturns201(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'senderEmail' => 'sender@example.com',
            'senderName' => 'Test Sender',
            'recipients' => ['recipient@example.com'],
            'isPublic' => false,
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('ownerToken', $data);
        self::assertArrayHasKey('reference', $data);
        self::assertArrayHasKey('uploadKey', $data);
        self::assertFalse($data['isPublic']);
    }

    public function testCreatePublicTransferReturns201(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'isPublic' => true,
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['isPublic']);
    }

    public function testCreateTransferWithMissingFieldsReturns422(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'isPublic' => false,
        ]));

        self::assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $data);
    }

    public function testGetTransferMetadata(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'senderEmail' => 'sender@example.com',
            'recipients' => ['recipient@example.com'],
            'isPublic' => false,
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('GET', '/api/transfer/'.$token);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('pending', $data['status']);
        self::assertArrayHasKey('expiresAt', $data);
    }

    public function testGetNonExistentTransferReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/transfer/nonexistent-token');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAbandonPendingTransfer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'senderEmail' => 'sender@example.com',
            'recipients' => ['recipient@example.com'],
            'isPublic' => false,
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('DELETE', '/api/transfer/'.$token.'/abandon');

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testAbandonNonExistentTransferReturnsOk(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/transfer/nonexistent-token/abandon');

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testResumeCheckForNonExistentTokenReturnsGone(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/transfer/nonexistent-token/resume-check');

        self::assertResponseStatusCodeSame(410);
    }

    public function testResumeCheckForPendingTransfer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/transfer', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'senderEmail' => 'sender@example.com',
            'recipients' => ['recipient@example.com'],
            'isPublic' => false,
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('GET', '/api/transfer/'.$token.'/resume-check');

        self::assertResponseIsSuccessful();
        self::assertTrue(json_decode($client->getResponse()->getContent(), true)['resumable']);
    }
}
