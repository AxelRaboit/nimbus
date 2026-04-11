<?php

declare(strict_types=1);

namespace App\Enum;

enum PlanEnum: string
{
    case Free = 'free';
    case Pro = 'pro';

    public const DEFAULT = self::Free;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
