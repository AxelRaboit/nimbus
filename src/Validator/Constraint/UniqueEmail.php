<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class UniqueEmail extends Constraint
{
    public string $message = 'auth.register.error_email_taken';

    public function __construct(
        public readonly bool $excludeSelf = false,
        ?string $message = null,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options ?? [], $groups, $payload);

        if (null !== $message) {
            $this->message = $message;
        }
    }
}
