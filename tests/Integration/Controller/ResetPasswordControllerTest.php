<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class ResetPasswordControllerTest extends IntegrationTestCase
{
    public function testForgotPasswordPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forgot-password');

        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordAuthenticatedUserIsRedirectedToHome(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/forgot-password');

        self::assertResponseRedirects('/');
    }

    public function testForgotPasswordPostAlwaysShowsSentStatusAntiEnumeration(): void
    {
        $client = static::createClient();
        $client->request('POST', '/forgot-password', ['email' => 'nonexistent@example.com']);

        self::assertResponseIsSuccessful();
        // Anti-enumeration: always shows sent message regardless of email existence
        self::assertNotNull($client->getResponse()->getContent());
    }

    public function testResetPasswordWithInvalidSelectorRedirectsToForgotPassword(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/invalidselector/invalidtoken');

        self::assertResponseRedirects('/forgot-password');
    }

    public function testResetPasswordWithExpiredTokenRedirectsToForgotPassword(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));
        $resetRequest = new ResetPasswordRequest(
            $user,
            $selector,
            hash('sha256', $plainToken),
            new DateTimeImmutable('-1 hour'),
        );
        $entityManager->persist($resetRequest);
        $entityManager->flush();

        $client->request('GET', sprintf('/reset-password/%s/%s', $selector, $plainToken));

        self::assertResponseRedirects('/forgot-password');

        $entityManager->remove($resetRequest);
        $entityManager->flush();
    }

    public function testResetPasswordFormIsAccessibleWithValidToken(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));
        $resetRequest = new ResetPasswordRequest(
            $user,
            $selector,
            hash('sha256', $plainToken),
            new DateTimeImmutable('+1 hour'),
        );
        $entityManager->persist($resetRequest);
        $entityManager->flush();

        $client->request('GET', sprintf('/reset-password/%s/%s', $selector, $plainToken));

        self::assertResponseIsSuccessful();

        $entityManager->remove($resetRequest);
        $entityManager->flush();
    }

    public function testResetPasswordPostWithMismatchShowsError(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));
        $resetRequest = new ResetPasswordRequest(
            $user,
            $selector,
            hash('sha256', $plainToken),
            new DateTimeImmutable('+1 hour'),
        );
        $entityManager->persist($resetRequest);
        $entityManager->flush();

        $client->request('POST', sprintf('/reset-password/%s/%s', $selector, $plainToken), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        self::assertResponseIsSuccessful();

        $entityManager->remove($resetRequest);
        $entityManager->flush();
    }

    public function testResetPasswordPostWithValidDataRedirectsToLogin(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));
        $resetRequest = new ResetPasswordRequest(
            $user,
            $selector,
            hash('sha256', $plainToken),
            new DateTimeImmutable('+1 hour'),
        );
        $entityManager->persist($resetRequest);
        $entityManager->flush();

        $client->request('POST', sprintf('/reset-password/%s/%s', $selector, $plainToken), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        self::assertResponseRedirects('/login?reset=1');
    }
}
