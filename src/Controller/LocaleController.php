<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\LocaleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    #[Route('/locale', name: 'app_locale_switch', methods: [HttpMethodEnum::Post->value])]
    public function switch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $locale = $data['locale'] ?? LocaleEnum::default()->value;

        $localeEnum = LocaleEnum::tryFrom($locale);
        if (null === $localeEnum) {
            return $this->json(['error' => 'Unsupported locale'], Response::HTTP_BAD_REQUEST);
        }

        $request->getSession()->set('_locale', $locale);

        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setLocale($localeEnum);
            $this->entityManager->flush();
        }

        return $this->json(['locale' => $locale]);
    }
}
