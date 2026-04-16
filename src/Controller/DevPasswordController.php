<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DevPasswordController extends AbstractController
{
    public function __construct(
        private readonly string $devPassword,
    ) {}

    #[Route('/dev-access', name: 'dev_access_show', methods: [Request::METHOD_GET])]
    public function show(): Response
    {
        return $this->render('dev_password/index.html.twig');
    }

    #[Route('/dev-access', name: 'dev_access_check', methods: [Request::METHOD_POST])]
    public function check(Request $request): Response
    {
        if ($request->request->getString('password') === $this->devPassword) {
            $request->getSession()->set('dev_password_verified', true);
            $intended = $request->getSession()->get('dev_password_intended', '/');
            $request->getSession()->remove('dev_password_intended');

            return $this->redirect($intended);
        }

        return $this->render('dev_password/index.html.twig', [
            'error' => 'Mot de passe incorrect.',
        ]);
    }
}
