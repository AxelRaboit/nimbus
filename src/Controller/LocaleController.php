<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    private const SUPPORTED = ['fr', 'en', 'es', 'de'];

    #[Route('/locale', name: 'app_locale_switch', methods: ['POST'])]
    public function switch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $locale = $data['locale'] ?? 'fr';

        if (!in_array($locale, self::SUPPORTED, true)) {
            return $this->json(['error' => 'Unsupported locale'], Response::HTTP_BAD_REQUEST);
        }

        $request->getSession()->set('_locale', $locale);

        return $this->json(['locale' => $locale]);
    }
}
