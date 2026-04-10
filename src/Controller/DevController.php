<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ApplicationParameterRepository;
use App\Service\DevStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev')]
#[IsGranted('ROLE_DEV')]
class DevController extends AbstractController
{
    public function __construct(
        private readonly DevStatsService $statsService,
        private readonly ApplicationParameterRepository $parameterRepository,
    ) {}

    #[Route('', name: 'dev_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dev/index.html.twig', [
            'tab' => 'stats',
            'stats' => $this->statsService->getStats(),
        ]);
    }

    #[Route('/parameters/{key}', name: 'dev_parameter_update', methods: ['PATCH'])]
    public function updateParameter(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        $this->parameterRepository->set($key, $value);

        return $this->json(['key' => $key, 'value' => $value]);
    }

    #[Route('/transfers', name: 'dev_transfers')]
    public function transfers(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $status = $request->query->get('status', '');

        return $this->render('dev/index.html.twig', [
            'tab' => 'transfers',
            'transfers' => $this->statsService->getAllTransfers($page, $status),
            'status' => $status,
        ]);
    }
}
