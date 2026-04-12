<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRequest;
use App\Manager\AccessRequestManager;
use App\Repository\AccessRequestRepository;
use App\Service\AccessChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccessRequestController extends AbstractController
{
    public function __construct(
        private readonly AccessChecker $accessChecker,
    ) {}

    #[Route('/access-request/{token}/approve', name: 'access_request_approve', methods: ['GET'])]
    public function approve(
        string $token,
        AccessRequestRepository $repository,
        AccessRequestManager $manager,
    ): Response {
        $accessRequest = $repository->findByToken($token);

        if (!$accessRequest instanceof AccessRequest || !$accessRequest->isPending()) {
            return $this->render('access_request/result.html.twig', [
                'success' => false,
                'reason' => 'not_found',
            ]);
        }

        if ($accessRequest->isAdminLinkExpired()) {
            return $this->render('access_request/result.html.twig', [
                'success' => false,
                'reason' => 'expired',
            ]);
        }

        $manager->approve($accessRequest);

        return $this->render('access_request/result.html.twig', [
            'success' => true,
            'reason' => 'approved',
            'requesterEmail' => $accessRequest->getRequesterEmail(),
        ]);
    }

    #[Route('/access-request/{accessToken}/grant', name: 'access_request_grant', methods: ['GET'])]
    public function grant(
        string $accessToken,
        AccessRequestRepository $repository,
        AccessRequestManager $manager,
        Request $request,
    ): Response {
        $accessRequest = $repository->findByAccessToken($accessToken);

        if (!$accessRequest instanceof AccessRequest || !$accessRequest->isApproved()) {
            return $this->render('access_request/result.html.twig', [
                'success' => false,
                'reason' => 'not_found',
            ]);
        }

        if ($accessRequest->isAccessTokenExpired()) {
            return $this->render('access_request/result.html.twig', [
                'success' => false,
                'reason' => 'expired',
            ]);
        }

        $this->grantAccessRequest($request, $accessRequest);
        $manager->consume($accessRequest);

        return $this->redirectToRoute('home');
    }

    private function grantAccessRequest(Request $request, AccessRequest $accessRequest): void
    {
        $this->accessChecker->grant($request);

        if (null !== $accessRequest->getGrantedFileSizeMb()) {
            $request->getSession()->set('custom_file_size_mb', $accessRequest->getGrantedFileSizeMb());
        }
    }
}
