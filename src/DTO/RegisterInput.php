<?php

declare(strict_types=1);

namespace App\DTO;

use App\Validator\Constraint\UniqueEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'auth.register.error_name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'auth.register.error_email_invalid')]
        #[Assert\Email(message: 'auth.register.error_email_invalid')]
        #[UniqueEmail(message: 'auth.register.error_email_taken')]
        public string $email,
        #[Assert\NotBlank(message: 'auth.register.error_password_length')]
        #[Assert\Length(min: 8, minMessage: 'auth.register.error_password_length')]
        public string $password,
        public string $passwordConfirmation,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: mb_trim($request->request->get('name', '')),
            email: mb_trim($request->request->get('email', '')),
            password: $request->request->get('password', ''),
            passwordConfirmation: $request->request->get('password_confirmation', ''),
        );
    }
}
