<?php

declare(strict_types=1);

namespace App\Tests\Unit\Manager;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Manager\PasswordResetManager;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use Closure;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetManagerTest extends TestCase
{
    public function testSendResetLinkDoesNothingForUnknownEmail(): void
    {
        $userRepo = $this->createStub(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $this->buildManager(userRepository: $userRepo, mailer: $mailer, entityManager: $entityManager)
            ->sendResetLink('unknown@example.com');
    }

    public function testSendResetLinkSendsEmailForKnownUser(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $userRepo = $this->createStub(UserRepository::class);
        $userRepo->method('findOneBy')->willReturn($user);

        $resetRepo = $this->createStub(ResetPasswordRequestRepository::class);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $localeSwitcher = $this->createStub(LocaleSwitcher::class);
        $localeSwitcher->method('runWithLocale')->willReturnCallback(
            static fn (string $locale, Closure $callback): mixed => $callback(),
        );

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('subject or body');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/reset');

        $this->buildManager(
            entityManager: $entityManager,
            userRepository: $userRepo,
            resetRepo: $resetRepo,
            mailer: $mailer,
            urlGenerator: $urlGenerator,
            translator: $translator,
            localeSwitcher: $localeSwitcher,
        )->sendResetLink('user@example.com');
    }

    public function testValidateTokenReturnsNullForMissingSelector(): void
    {
        $resetRepo = $this->createStub(ResetPasswordRequestRepository::class);
        $resetRepo->method('findBySelector')->willReturn(null);

        $result = $this->buildManager(resetRepo: $resetRepo)->validateToken('bad-selector', 'token');

        self::assertNull($result);
    }

    public function testValidateTokenReturnsNullForExpiredRequest(): void
    {
        $user = new User();
        $resetRequest = new ResetPasswordRequest(
            $user,
            'selector',
            hash('sha256', 'plain'),
            new DateTimeImmutable('-1 hour'),
        );

        $resetRepo = $this->createStub(ResetPasswordRequestRepository::class);
        $resetRepo->method('findBySelector')->willReturn($resetRequest);

        $result = $this->buildManager(resetRepo: $resetRepo)->validateToken('selector', 'plain');

        self::assertNull($result);
    }

    public function testValidateTokenReturnsNullForWrongToken(): void
    {
        $user = new User();
        $resetRequest = new ResetPasswordRequest(
            $user,
            'selector',
            hash('sha256', 'correct-token'),
            new DateTimeImmutable('+1 hour'),
        );

        $resetRepo = $this->createStub(ResetPasswordRequestRepository::class);
        $resetRepo->method('findBySelector')->willReturn($resetRequest);

        $result = $this->buildManager(resetRepo: $resetRepo)->validateToken('selector', 'wrong-token');

        self::assertNull($result);
    }

    public function testValidateTokenReturnsRequestForValidToken(): void
    {
        $user = new User();
        $plainToken = 'my-plain-token';
        $resetRequest = new ResetPasswordRequest(
            $user,
            'selector',
            hash('sha256', $plainToken),
            new DateTimeImmutable('+1 hour'),
        );

        $resetRepo = $this->createStub(ResetPasswordRequestRepository::class);
        $resetRepo->method('findBySelector')->willReturn($resetRequest);

        $result = $this->buildManager(resetRepo: $resetRepo)->validateToken('selector', $plainToken);

        self::assertSame($resetRequest, $result);
    }

    public function testResetPasswordHashesAndPersistsNewPassword(): void
    {
        $user = new User();
        $resetRequest = new ResetPasswordRequest(
            $user,
            'selector',
            hash('sha256', 'token'),
            new DateTimeImmutable('+1 hour'),
        );

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())
            ->method('hashPassword')
            ->with($user, 'new-password')
            ->willReturn('hashed-password');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($resetRequest);
        $entityManager->expects(self::once())->method('flush');

        $this->buildManager(hasher: $hasher, entityManager: $entityManager)
            ->resetPassword($resetRequest, 'new-password');

        self::assertSame('hashed-password', $user->getPassword());
    }

    public function testResetPasswordDeletesResetRequest(): void
    {
        $user = new User();
        $resetRequest = new ResetPasswordRequest(
            $user,
            'selector',
            hash('sha256', 'token'),
            new DateTimeImmutable('+1 hour'),
        );

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hash');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($resetRequest);
        $entityManager->expects(self::once())->method('flush');

        $this->buildManager(hasher: $hasher, entityManager: $entityManager)
            ->resetPassword($resetRequest, 'newpass');
    }

    private function buildManager(
        ?EntityManagerInterface $entityManager = null,
        ?UserRepository $userRepository = null,
        ?ResetPasswordRequestRepository $resetRepo = null,
        ?UserPasswordHasherInterface $hasher = null,
        ?MailerInterface $mailer = null,
        ?UrlGeneratorInterface $urlGenerator = null,
        ?TranslatorInterface $translator = null,
        ?LocaleSwitcher $localeSwitcher = null,
    ): PasswordResetManager {
        return new PasswordResetManager(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $userRepository ?? $this->createStub(UserRepository::class),
            $resetRepo ?? $this->createStub(ResetPasswordRequestRepository::class),
            $hasher ?? $this->createStub(UserPasswordHasherInterface::class),
            $mailer ?? $this->createStub(MailerInterface::class),
            $urlGenerator ?? $this->createStub(UrlGeneratorInterface::class),
            $translator ?? $this->createStub(TranslatorInterface::class),
            $localeSwitcher ?? $this->createStub(LocaleSwitcher::class),
        );
    }
}
