<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\HttpMethodEnum;
use App\Service\ImpersonationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ImpersonationController extends AbstractController
{
    public function __construct(
        private readonly ImpersonationService $impersonationService,
    ) {}

    #[Route('/impersonation/leave', name: 'impersonation_leave', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ROLE_USER')]
    public function leave(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('dev', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($this->impersonationService->isImpersonating()) {
            $this->impersonationService->leave();
        }

        return $this->redirectToRoute('dev_users');
    }
}
