<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\PlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRoleEnum::User->value)]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->planService->canAccessMyTransfers($user)) {
            return $this->redirectToRoute('plan');
        }

        return $this->render('dashboard/index.html.twig');
    }
}
