<?php

declare(strict_types=1);

namespace App\Service;

final class EmailValidator
{
    public static function isValid(string $email): bool
    {
        return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
