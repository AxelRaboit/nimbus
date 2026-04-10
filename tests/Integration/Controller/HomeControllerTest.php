<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Tests\Integration\IntegrationTestCase;

final class HomeControllerTest extends IntegrationTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testHomePageWithAuthenticatedUserIsAccessible(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
