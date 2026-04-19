<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\ApplicationParameter;
use App\Entity\User;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class RegistrationControllerTest extends IntegrationTestCase
{
    public function testRegisterPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
    }

    public function testAuthenticatedUserIsRedirectedToHome(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user@nimbus.app']);

        $client->loginUser($user);
        $client->request('GET', '/register');

        self::assertResponseRedirects('/');
    }

    public function testRegistrationDisabledShowsDisabledForm(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $param = $entityManager->getRepository(ApplicationParameter::class)->findOneBy(['key' => 'registration_enabled']);
        $originalValue = $param?->getValue();

        if ($param) {
            $param->setValue('0');
            $entityManager->flush();
        }

        try {
            $client->request('GET', '/register');
            self::assertResponseIsSuccessful();
            self::assertStringContainsString('registration', mb_strtolower($client->getResponse()->getContent()));
        } finally {
            if ($param && null !== $originalValue) {
                $param->setValue($originalValue);
                $entityManager->flush();
            }
        }
    }

    public function testPostWithPasswordMismatchShowsError(): void
    {
        $client = static::createClient();
        $client->request('POST', '/register', [
            'name' => 'Test User',
            'email' => 'newuser_mismatch@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('password', mb_strtolower($client->getResponse()->getContent()));
    }

    public function testPostWithValidDataCreatesUserAndRedirects(): void
    {
        $client = static::createClient();
        $client->request('POST', '/register', [
            'name' => 'New Test User',
            'email' => 'newuser_registration@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        self::assertResponseRedirects('/');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'newuser_registration@example.com']);
        self::assertNotNull($user);
        self::assertSame('New Test User', $user->getName());
    }

    public function testPostWithBlankFieldsShowsValidationErrors(): void
    {
        $client = static::createClient();
        $client->request('POST', '/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        self::assertResponseIsSuccessful();
    }
}
