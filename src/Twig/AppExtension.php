<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly string $projectDir) {}

    public function getGlobals(): array
    {
        $versionFile = $this->projectDir.'/VERSION';

        return [
            'appVersion' => file_exists($versionFile) ? mb_trim(file_get_contents($versionFile)) : 'dev',
        ];
    }
}
