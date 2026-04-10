<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Tests\Integration\IntegrationTestCase;

final class ProfileControllerTest extends IntegrationTestCase
{
    public function testProfilePageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        self::assertResponseRedirects('/login');
    }

    public function testProfilePageIsAccessibleWhenAuthenticated(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
    }

    public function testUpdateProfileWithValidData(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('POST', '/profile/update', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Updated Name',
            'email' => 'user@nimbus.app',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
    }

    public function testUpdateProfileWithTakenEmailReturnsError(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('POST', '/profile/update', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Test User',
            'email' => 'dev@nimbus.app',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('email', $data['errors']);
    }

    public function testChangePasswordWithWrongCurrentPasswordReturnsError(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('POST', '/profile/password', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('current_password', $data['errors']);
    }
}
