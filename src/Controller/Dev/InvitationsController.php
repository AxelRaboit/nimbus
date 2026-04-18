<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Service\InvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/invitations')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class InvitationsController extends AbstractController
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    #[Route('', name: 'dev_invitations')]
    public function index(): Response
    {
        return $this->render('dev/index.html.twig', [
            'tab' => 'invitations',
        ]);
    }

    #[Route('/send', name: 'dev_invitation_send', methods: [HttpMethodEnum::Post->value])]
    public function send(Request $request): Response
    {
        $email = $request->request->getString('email');

        if ('' === $email || '0' === $email) {
            $this->addFlash('error', "L'adresse email est requise.");

            return $this->redirectToRoute('dev_invitations');
        }

        $this->invitationService->send(
            $email,
            $request->request->getString('message'),
            $request->request->getString('credential_email'),
            $request->request->getString('credential_password'),
        );

        $this->addFlash('success', sprintf('Invitation envoyée à %s.', $email));

        return $this->redirectToRoute('dev_invitations');
    }
}
