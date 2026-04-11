<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\AllowedExtensionEnum;
use App\Enum\ExpiryOptionEnum;
use App\Repository\ApplicationParameterRepository;
use App\Service\PlanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly string $accessPassword,
        private readonly PlanService $planService,
    ) {}

    #[Route('/', name: 'home')]
    public function index(Request $request, ApplicationParameterRepository $params): Response
    {
        $accessPasswordEnabled = '' !== $this->accessPassword;
        $accessGranted = $accessPasswordEnabled
            ? (bool) $request->getSession()->get('access_granted', false)
            : true;

        /** @var User|null $user */
        $user = $this->getUser();

        $maxSizeMb = $user ? $this->planService->getMaxSizeMb($user) : $this->planService->getFreeMaxSizeMb();
        $maxFiles = $user ? $this->planService->getMaxFiles($user) : $this->planService->getFreeMaxFiles();
        $maxRecipients = $user ? $this->planService->getMaxRecipients($user) : $this->planService->getFreeMaxRecipients();
        $maxExpiryHours = $user ? $this->planService->getMaxExpiryHours($user) : $this->planService->getFreeMaxExpiryHours();
        $maxExpiryDays = (int) ceil($maxExpiryHours / 24);

        return $this->render('home/index.html.twig', [
            'maxSizeMb' => $maxSizeMb,
            'maxFiles' => $maxFiles,
            'maxRecipients' => $maxRecipients,
            'maxExpiryDays' => $maxExpiryDays,
            'expiryOptions' => ExpiryOptionEnum::validOptions($maxExpiryDays),
            'extensionGroups' => AllowedExtensionEnum::groupedValues(),
            'accessPasswordEnabled' => $accessPasswordEnabled,
            'accessGranted' => $accessGranted,
        ]);
    }
}
