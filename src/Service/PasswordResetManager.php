<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PasswordResetManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ResetPasswordRequestRepository $resetRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Generates a reset token and sends the email if the user exists.
     * Silently does nothing when the email is not found (anti-enumeration).
     */
    public function sendResetLink(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            return;
        }

        $this->resetRepo->deleteByUser($user);

        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);
        $expiresAt = new DateTimeImmutable('+1 hour');

        $resetRequest = new ResetPasswordRequest($user, $selector, $hashedToken, $expiresAt);
        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate('app_reset_password', [
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = (new Email())
            ->from($_ENV['MAILER_FROM_ADDRESS'] ?? 'noreply@nimbus.dev')
            ->to($user->getEmail())
            ->subject($this->translator->trans('auth.forgot_password.email_subject'))
            ->html(sprintf(
                '<p>%s</p><p><a href="%s">%s</a></p><p>%s</p>',
                $this->translator->trans('auth.forgot_password.email_body'),
                $resetUrl,
                $resetUrl,
                $this->translator->trans('auth.forgot_password.email_expiry'),
            ));

        $this->mailer->send($message);
    }

    /**
     * Validates the reset token and returns the request if valid.
     * Returns null when the token is invalid or expired.
     */
    public function validateToken(string $selector, string $token): ?ResetPasswordRequest
    {
        $resetRequest = $this->resetRepo->findBySelector($selector);

        if (!$resetRequest instanceof ResetPasswordRequest || $resetRequest->isExpired()) {
            return null;
        }

        if (!hash_equals($resetRequest->getHashedToken(), hash('sha256', $token))) {
            return null;
        }

        return $resetRequest;
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $user = $resetRequest->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

        $this->entityManager->remove($resetRequest);
        $this->entityManager->flush();
    }
}
