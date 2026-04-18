<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Contract\AccessRequestManagerInterface;
use App\Entity\AccessRequest;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\AccessRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/access-requests')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class AccessRequestsController extends AbstractController
{
    public function __construct(
        private readonly AccessRequestRepository $accessRequestRepository,
        private readonly AccessRequestManagerInterface $accessRequestManager,
    ) {}

    #[Route('', name: 'dev_access_requests')]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->accessRequestRepository->findPaginatedAdmin($page);

        return $this->render('dev/index.html.twig', [
            'tab' => 'access_requests',
            'accessRequests' => [
                ...$result,
                'items' => array_map($this->serialize(...), $result['items']),
            ],
        ]);
    }

    #[Route('/{id}/approve', name: 'dev_access_request_approve', methods: [HttpMethodEnum::Post->value])]
    public function approve(AccessRequest $accessRequest, Request $request): Response
    {
        if ($accessRequest->isPending()) {
            $raw = $request->request->get('granted_file_size_mb', '');
            $grantedFileSizeMb = '' !== $raw ? abs((int) $raw) : null;

            $this->accessRequestManager->approve($accessRequest, $grantedFileSizeMb ?: null);
        }

        return $this->redirectToRoute('dev_access_requests');
    }

    #[Route('/purge-approved', name: 'dev_access_request_purge_approved', methods: [HttpMethodEnum::Post->value])]
    public function purgeApproved(): Response
    {
        $this->accessRequestRepository->deleteProcessed();

        return $this->redirectToRoute('dev_access_requests');
    }

    #[Route('/{id}/reject', name: 'dev_access_request_reject', methods: [HttpMethodEnum::Post->value])]
    public function reject(AccessRequest $accessRequest): Response
    {
        if ($accessRequest->isPending()) {
            $this->accessRequestManager->reject($accessRequest);
        }

        return $this->redirectToRoute('dev_access_requests');
    }

    private function serialize(AccessRequest $accessRequest): array
    {
        return [
            'id' => $accessRequest->getId(),
            'requesterEmail' => $accessRequest->getRequesterEmail(),
            'requesterName' => $accessRequest->getRequesterName(),
            'message' => $accessRequest->getMessage(),
            'status' => $accessRequest->getStatus()->value,
            'expiresAt' => $accessRequest->getExpiresAt()->format('c'),
            'createdAt' => $accessRequest->getCreatedAt()->format('c'),
            'requestedFileSizeMb' => $accessRequest->getRequestedFileSizeMb(),
            'grantedFileSizeMb' => $accessRequest->getGrantedFileSizeMb(),
        ];
    }
}
