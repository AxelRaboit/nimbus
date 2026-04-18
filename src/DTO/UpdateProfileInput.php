<?php

declare(strict_types=1);

namespace App\DTO;

use App\Validator\Constraint\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'auth.register.error_name_required')]
        public readonly string $name,
        #[Assert\NotBlank(message: 'auth.register.error_email_invalid')]
        #[Assert\Email(message: 'auth.register.error_email_invalid')]
        #[UniqueEmail(excludeSelf: true, message: 'auth.register.error_email_taken')]
        public readonly string $email,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: mb_trim((string) ($data['name'] ?? '')),
            email: mb_trim((string) ($data['email'] ?? '')),
        );
    }
}
