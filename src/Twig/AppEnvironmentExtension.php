<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\PlanService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class AppEnvironmentExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly string $appEnv) {}

    public function getGlobals(): array
    {
        return [
            'isProd' => 'prod' === $this->appEnv,
            'demoMaxFileSizeMb' => PlanService::DEMO_MAX_FILE_SIZE_MB,
            'demoMaxTotalSizeMb' => PlanService::DEMO_MAX_TOTAL_SIZE_MB,
        ];
    }
}
