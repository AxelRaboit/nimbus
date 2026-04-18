<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Contract\AccessRequestManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\AccessRequestInput;
use App\Enum\HttpMethodEnum;
use App\Service\AccessChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function is_array;

#[Route('/api/home')]
class HomeApiController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly AccessChecker $accessChecker,
        private readonly AccessRequestManagerInterface $accessRequestManager,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/verify-access', name: 'api_home_verify_access', methods: [HttpMethodEnum::Post->value])]
    public function verifyAccess(Request $request, RateLimiterFactoryInterface $homeVerifyAccessLimiter): JsonResponse
    {
        if (!$homeVerifyAccessLimiter->create($request->getClientIp())->consume()->isAccepted()) {
            return $this->json(['error' => 'too_many_attempts'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$this->accessChecker->isEnabled()) {
            return $this->json(['ok' => true]);
        }

        $data = json_decode($request->getContent(), true);
        $submitted = is_array($data) ? ($data['password'] ?? '') : '';

        if (!$this->accessChecker->verify($submitted)) {
            return $this->json(['error' => 'wrong_password'], JsonResponse::HTTP_FORBIDDEN);
        }

        $this->accessChecker->grant($request);

        return $this->json(['ok' => true]);
    }

    #[Route('/request-access', name: 'api_home_request_access', methods: [HttpMethodEnum::Post->value])]
    public function requestAccess(Request $request, RateLimiterFactoryInterface $homeRequestAccessLimiter): JsonResponse
    {
        if (!$homeRequestAccessLimiter->create($request->getClientIp())->consume()->isAccepted()) {
            return $this->json(['error' => 'too_many_attempts'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$this->accessChecker->isEnabled()) {
            return $this->json(['error' => 'access_password_not_enabled'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'invalid_payload'], Response::HTTP_BAD_REQUEST);
        }

        $input = AccessRequestInput::fromArray($data);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['error' => 'invalid_email'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->accessRequestManager->create($input->email, $input->name, $input->message, $input->requestedFileSizeMb);

        return $this->json(['ok' => true], Response::HTTP_CREATED);
    }
}
