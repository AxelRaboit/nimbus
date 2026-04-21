<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ImpersonationService
{
    private const string SESSION_KEY = 'impersonator_id';

    public function __construct(
        private RequestStack $requestStack,
        private UserRepository $userRepository,
        private Security $security,
    ) {}

    public function impersonate(User $target, User $impersonator): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $impersonator->getId());
        $session->set('_locale', $target->getLocale()->value);

        $this->security->login($target, 'form_login', 'main');
    }

    public function leave(): void
    {
        $session = $this->requestStack->getSession();
        $impersonatorId = $session->get(self::SESSION_KEY);
        $session->remove(self::SESSION_KEY);

        $impersonator = $this->userRepository->find($impersonatorId);
        if (!$impersonator instanceof User) {
            return;
        }

        $session->set('_locale', $impersonator->getLocale()->value);
        $this->security->login($impersonator, 'form_login', 'main');
    }

    public function isImpersonating(): bool
    {
        return null !== $this->requestStack->getSession()->get(self::SESSION_KEY);
    }
}
