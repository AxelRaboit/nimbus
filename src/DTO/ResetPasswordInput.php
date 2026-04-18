<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'auth.register.error_password_length')]
        #[Assert\Length(min: 8, minMessage: 'auth.register.error_password_length')]
        public readonly string $password,
        public readonly string $passwordConfirmation,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            password: $request->request->get('password', ''),
            passwordConfirmation: $request->request->get('password_confirmation', ''),
        );
    }
}
