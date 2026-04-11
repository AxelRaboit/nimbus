<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\HttpMethodEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function is_array;

#[Route('/api/home')]
class HomeApiController extends AbstractController
{
    public function __construct(
        private readonly string $accessPassword,
    ) {}

    #[Route('/verify-access', name: 'api_home_verify_access', methods: [HttpMethodEnum::Post->value])]
    public function verifyAccess(Request $request): JsonResponse
    {
        if ('' === $this->accessPassword) {
            return $this->json(['ok' => true]);
        }

        $data = json_decode($request->getContent(), true);
        $submitted = is_array($data) ? ($data['password'] ?? '') : '';

        if (!hash_equals($this->accessPassword, $submitted)) {
            return $this->json(['error' => 'wrong_password'], JsonResponse::HTTP_FORBIDDEN);
        }

        $request->getSession()->set('access_granted', true);

        return $this->json(['ok' => true]);
    }
}
