<?php

declare(strict_types=1);

namespace App\Enum;

enum StorageBackendEnum: string
{
    case Local = 'local';
    case R2 = 'r2';
}
