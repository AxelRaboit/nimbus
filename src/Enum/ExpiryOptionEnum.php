<?php

declare(strict_types=1);

namespace App\Enum;

enum ExpiryOptionEnum: int
{
    // Heures
    case OneHour = 1;
    case ThreeHours = 3;
    case SixHours = 6;
    case TwelveHours = 12;

    // Jours (en heures)
    case OneDay = 24;
    case ThreeDays = 72;
    case SevenDays = 168;
    case FourteenDays = 336;
    case ThirtyDays = 720;

    /**
     * Returns valid options (in hours) up to $maxDays.
     * All standard options strictly below $maxDays * 24 are included,
     * and $maxDays * 24 is always appended.
     *
     * @return int[]
     */
    public static function validOptions(int $maxDays): array
    {
        $maxHours = $maxDays * 24;

        $options = array_values(array_filter(
            array_column(self::cases(), 'value'),
            fn (int $h): bool => $h < $maxHours,
        ));

        $options[] = $maxHours;

        return $options;
    }
}
