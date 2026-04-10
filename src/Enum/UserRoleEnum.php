<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRoleEnum: string
{
    case User = 'ROLE_USER';
    case Dev = 'ROLE_DEV';
}
