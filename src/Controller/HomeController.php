<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\AllowedExtensionEnum;
use App\Enum\ExpiryOptionEnum;
use App\Repository\ApplicationParameterRepository;
use App\Service\AccessChecker;
use App\Service\PlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly AccessChecker $accessChecker,
        private readonly PlanService $planService,
    ) {}

    #[Route('/', name: 'home')]
    public function index(Request $request, ApplicationParameterRepository $params): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $accessPasswordEnabled = $this->accessChecker->isEnabled();
        $accessGranted = $this->accessChecker->isGranted($request, $user);

        $sessionCustomSize = (int) $request->getSession()->get('custom_file_size_mb', 0);
        $proMaxSizeMb = $this->planService->getProMaxSizeMb();
        $maxSizeMb = $user
            ? $this->planService->getMaxSizeMb($user)
            : ($sessionCustomSize > 0 ? min($sessionCustomSize, $proMaxSizeMb) : $this->planService->getFreeMaxSizeMb());
        $maxFiles = $user ? $this->planService->getMaxFiles($user) : $this->planService->getFreeMaxFiles();
        $maxRecipients = $user ? $this->planService->getMaxRecipients($user) : $this->planService->getFreeMaxRecipients();
        $maxExpiryHours = $user ? $this->planService->getMaxExpiryHours($user) : $this->planService->getFreeMaxExpiryHours();
        $maxExpiryDays = (int) ceil($maxExpiryHours / 24);

        return $this->render('home/index.html.twig', [
            'maxSizeMb' => $maxSizeMb,
            'proMaxSizeMb' => $proMaxSizeMb,
            'maxFiles' => $maxFiles,
            'maxRecipients' => $maxRecipients,
            'maxExpiryDays' => $maxExpiryDays,
            'tusCleanupMaxAgeHours' => $this->planService->getTusCleanupMaxAgeHours(),
            'expiryOptions' => ExpiryOptionEnum::validOptions($maxExpiryDays),
            'extensionGroups' => AllowedExtensionEnum::groupedValues(),
            'accessPasswordEnabled' => $accessPasswordEnabled,
            'accessGranted' => $accessGranted,
            'isPro' => $user && $this->planService->isPro($user),
            'registrationEnabled' => '0' !== $params->get('registration_enabled', '1'),
        ]);
    }
}
