<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Enum\PlanEnum;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class PlanControllerTest extends IntegrationTestCase
{
    public function testPlanPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/plan');

        self::assertResponseRedirects('/login');
    }

    public function testPlanPageIsAccessibleWhenAuthenticated(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/plan');

        self::assertResponseIsSuccessful();
    }

    public function testUpgradeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/plan/upgrade');

        self::assertResponseRedirects('/login');
    }

    public function testUpgradeWithInvalidCsrfReturnsForbidden(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('POST', '/plan/upgrade', ['_token' => 'invalid-token']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUpgradeWithValidCsrfSetsPlanProAndRedirects(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/plan');

        preg_match('/data-csrf-token="([^"]+)"/', $client->getResponse()->getContent(), $matches);
        $csrfToken = $matches[1] ?? '';

        $client->request('POST', '/plan/upgrade', ['_token' => $csrfToken]);

        self::assertResponseRedirects('/plan');

        $updatedUser = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);
        self::assertSame(PlanEnum::Pro, $updatedUser->getPlan());
        self::assertNotNull($updatedUser->getTrialEndsAt());
    }

    public function testDowngradeWithValidCsrfSetsPlanFreeAndRedirects(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $user->setPlan(PlanEnum::Pro);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/plan');

        preg_match('/data-csrf-token="([^"]+)"/', $client->getResponse()->getContent(), $matches);
        $csrfToken = $matches[1] ?? '';

        $client->request('POST', '/plan/downgrade', ['_token' => $csrfToken]);

        self::assertResponseRedirects('/plan');

        $updatedUser = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);
        self::assertSame(PlanEnum::Free, $updatedUser->getPlan());
        self::assertNull($updatedUser->getTrialEndsAt());
    }

    public function testDowngradeWithInvalidCsrfReturnsForbidden(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('POST', '/plan/downgrade', ['_token' => 'bad-token']);

        self::assertResponseStatusCodeSame(403);
    }
}
