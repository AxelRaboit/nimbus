<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Service\PlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(UserRoleEnum::User->value)]
class PlanController extends AbstractController
{
    public function __construct(
        private readonly PlanService $planService,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/plan', name: 'plan')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('plan/index.html.twig', [
            'isPro' => $this->planService->isPro($user),
            'trialEndsAt' => $user->getTrialEndsAt()?->format('c'),
            'proPrice' => PlanService::PRO_PRICE,
            'freeMaxSizeMb' => $this->planService->getFreeMaxSizeMb(),
            'freeMaxFiles' => $this->planService->getFreeMaxFiles(),
            'freeMaxExpiryHours' => $this->planService->getFreeMaxExpiryHours(),
            'freeMaxRecipients' => $this->planService->getFreeMaxRecipients(),
            'proMaxSizeMb' => $this->planService->getProMaxSizeMb(),
            'proMaxFiles' => $this->planService->getProMaxFiles(),
            'proMaxExpiryDays' => $this->planService->getProMaxExpiryDays(),
            'proMaxRecipients' => $this->planService->getProMaxRecipients(),
            'upgradePath' => $this->generateUrl('plan_upgrade'),
            'downgradePath' => $this->generateUrl('plan_downgrade'),
        ]);
    }

    #[Route('/plan/upgrade', name: 'plan_upgrade', methods: [HttpMethodEnum::Post->value])]
    public function upgrade(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('plan_action', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($this->translator->trans('error.csrf_invalid'));
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->planService->upgrade($user);
        $this->addFlash('success', $this->translator->trans('plan.flash.upgraded'));

        return $this->redirectToRoute('plan');
    }

    #[Route('/plan/downgrade', name: 'plan_downgrade', methods: [HttpMethodEnum::Post->value])]
    public function downgrade(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('plan_action', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($this->translator->trans('error.csrf_invalid'));
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->planService->downgrade($user);
        $this->addFlash('success', $this->translator->trans('plan.flash.downgraded'));

        return $this->redirectToRoute('plan');
    }
}
