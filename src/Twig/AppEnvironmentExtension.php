<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class AppEnvironmentExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly string $appEnv) {}

    public function getGlobals(): array
    {
        return [
            'isProd' => 'prod' === $this->appEnv,
        ];
    }
}
