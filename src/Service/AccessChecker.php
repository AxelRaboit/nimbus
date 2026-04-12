<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\ApplicationParameter\NimbusApplicationParameterEnum;
use App\Repository\ApplicationParameterRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class AccessChecker
{
    public function __construct(
        private readonly string $accessPassword,
        private readonly ApplicationParameterRepository $params,
    ) {}

    public function isEnabled(): bool
    {
        return '' !== $this->accessPassword;
    }

    public function isGranted(Request $request, ?UserInterface $user): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($user instanceof User && $this->isAdminEmail($user->getEmail())) {
            return true;
        }

        return $request->getSession()->get('access_granted_hash') === md5($this->accessPassword);
    }

    public function grant(Request $request): void
    {
        $request->getSession()->set('access_granted_hash', md5($this->accessPassword));
    }

    public function verify(string $submitted): bool
    {
        return hash_equals($this->accessPassword, $submitted);
    }

    private function isAdminEmail(string $email): bool
    {
        $adminEmail = $this->params->get(
            NimbusApplicationParameterEnum::AdminEmail->value,
            NimbusApplicationParameterEnum::AdminEmail->getDefaultValue(),
        );

        return $email === $adminEmail;
    }
}
