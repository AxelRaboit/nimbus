<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class AccessRequestInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'invalid_email')]
        #[Assert\Email(message: 'invalid_email')]
        public readonly string $email,
        public readonly ?string $name,
        public readonly ?string $message,
        public readonly ?int $requestedFileSizeMb,
    ) {}

    public static function fromArray(array $data): self
    {
        $name = isset($data['name']) ? mb_trim((string) $data['name']) : null;
        $message = isset($data['message']) ? mb_trim((string) $data['message']) : null;
        $sizeMb = isset($data['requestedFileSizeMb']) ? abs((int) $data['requestedFileSizeMb']) : null;

        return new self(
            email: mb_trim((string) ($data['email'] ?? '')),
            name: $name ?: null,
            message: $message ?: null,
            requestedFileSizeMb: $sizeMb ?: null,
        );
    }
}
