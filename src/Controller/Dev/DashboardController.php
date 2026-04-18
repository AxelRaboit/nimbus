<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Enum\UserRoleEnum;
use App\Service\AdminStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AdminStatsService $adminStatsService,
    ) {}

    #[Route('/dashboard', name: 'dev_dashboard')]
    public function __invoke(): Response
    {
        return $this->render('dev/index.html.twig', [
            'tab' => 'stats',
            'stats' => $this->adminStatsService->getStats(),
        ]);
    }
}
