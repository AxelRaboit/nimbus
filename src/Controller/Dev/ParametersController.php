<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\ApplicationParameterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/parameters')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class ParametersController extends AbstractController
{
    public function __construct(
        private readonly ApplicationParameterRepository $parameterRepository,
    ) {}

    #[Route('', name: 'dev_parameters')]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->parameterRepository->findPaginated($page);

        return $this->render('dev/index.html.twig', [
            'tab' => 'parameters',
            'parameters' => [
                'items' => array_map(
                    fn ($parameter): array => ['key' => $parameter->getKey(), 'value' => $parameter->getValue(), 'description' => $parameter->getDescription()],
                    $result['items']
                ),
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
            ],
        ]);
    }

    #[Route('/{key}', name: 'dev_parameter_update', methods: [HttpMethodEnum::Patch->value])]
    public function update(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        $this->parameterRepository->set($key, $value);

        return $this->json(['key' => $key, 'value' => $value]);
    }
}
