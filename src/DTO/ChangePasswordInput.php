<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.password.error_current')]
        public readonly string $currentPassword,
        #[Assert\NotBlank(message: 'auth.register.error_password_length')]
        #[Assert\Length(min: 8, minMessage: 'auth.register.error_password_length')]
        public readonly string $password,
        public readonly string $passwordConfirmation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currentPassword: (string) ($data['current_password'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            passwordConfirmation: (string) ($data['password_confirmation'] ?? ''),
        );
    }
}
