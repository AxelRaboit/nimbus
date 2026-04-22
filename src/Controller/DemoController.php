<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController extends AbstractController
{
    #[Route('/demo/login', name: 'app_demo_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        Security $security,
        #[Autowire('%env(bool:DEMO_ENABLED)%')]
        bool $demoEnabled,
    ): RedirectResponse {
        if (!$demoEnabled) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('demo_login', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'error.csrf_invalid');

            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findDemoUser();

        if (!$user instanceof User) {
            $this->addFlash('error', 'demo.unavailable');

            return $this->redirectToRoute('app_login');
        }

        $security->login($user, firewallName: 'main');

        return $this->redirectToRoute('home');
    }
}
