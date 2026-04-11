<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transfer;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Manager\UserManager;
use App\Repository\ApplicationParameterRepository;
use App\Repository\TransferRepository;
use App\Repository\UserRepository;
use App\Service\AdminStatsService;
use App\Service\InvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev')]
#[IsGranted(UserRoleEnum::Dev->value)]
class DevController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransferRepository $transferRepository,
        private readonly ApplicationParameterRepository $parameterRepository,
        private readonly UserManager $userManager,
        private readonly AdminStatsService $adminStatsService,
        private readonly InvitationService $invitationService,
    ) {}

    #[Route('/dashboard', name: 'dev_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dev/index.html.twig', [
            'tab' => 'stats',
            'stats' => $this->adminStatsService->getStats(),
        ]);
    }

    #[Route('/dashboard/users', name: 'dev_users')]
    public function users(Request $request): Response
    {
        $search = $request->query->getString('search');
        $page = max(1, (int) $request->query->get('page', '1'));

        $result = $this->userRepository->findPaginatedForAdmin($page, $search ?: null);

        return $this->render('dev/index.html.twig', [
            'tab' => 'users',
            'users' => [
                'items' => array_map($this->serializeUser(...), $result['items']),
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
            ],
            'search' => $search,
        ]);
    }

    #[Route('/dashboard/users/{id}/delete', name: 'dev_user_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteUser(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('dev_users');
        }

        $this->userManager->delete($user);

        return $this->redirectToRoute('dev_users');
    }

    #[Route('/dashboard/users/{id}/toggle-role', name: 'dev_user_toggle_role', methods: [HttpMethodEnum::Post->value])]
    public function toggleRole(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');

            return $this->redirectToRoute('dev_users');
        }

        $this->userManager->toggleDevRole($user);

        return $this->redirectToRoute('dev_users');
    }

    #[Route('/dashboard/invitations', name: 'dev_invitations')]
    public function invitations(): Response
    {
        return $this->render('dev/index.html.twig', [
            'tab' => 'invitations',
        ]);
    }

    #[Route('/dashboard/invitations/send', name: 'dev_invitation_send', methods: [HttpMethodEnum::Post->value])]
    public function sendInvitation(Request $request): Response
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

    #[Route('/dashboard/parameters', name: 'dev_parameters')]
    public function parameters(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->parameterRepository->findPaginated($page);

        return $this->render('dev/index.html.twig', [
            'tab' => 'parameters',
            'parameters' => [
                'items' => array_map(
                    fn ($p): array => ['key' => $p->getKey(), 'value' => $p->getValue(), 'description' => $p->getDescription()],
                    $result['items']
                ),
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
            ],
        ]);
    }

    #[Route('/dashboard/parameters/{key}', name: 'dev_parameter_update', methods: [HttpMethodEnum::Patch->value])]
    public function updateParameter(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        $this->parameterRepository->set($key, $value);

        return $this->json(['key' => $key, 'value' => $value]);
    }

    #[Route('/dashboard/transfers', name: 'dev_transfers')]
    public function transfers(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $status = $request->query->get('status', '');

        $result = $this->transferRepository->findPaginatedAdmin($page, $status);

        return $this->render('dev/index.html.twig', [
            'tab' => 'transfers',
            'transfers' => [
                ...$result,
                'items' => array_map($this->serializeTransfer(...), $result['items']),
            ],
            'status' => $status,
        ]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'plan' => $user->getPlan()->value,
            'isDevRole' => in_array(UserRoleEnum::Dev->value, $user->getRoles(), true),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'trialEndsAt' => $user->getTrialEndsAt()?->format('c'),
        ];
    }

    private function serializeTransfer(Transfer $t): array
    {
        return [
            'id' => $t->getId(),
            'reference' => $t->getReference(),
            'ownerToken' => $t->getOwnerToken(),
            'senderEmail' => $t->getSenderEmail(),
            'senderName' => $t->getSenderName(),
            'status' => $t->getStatus()->value,
            'isExpired' => $t->isExpired(),
            'expiresAt' => $t->getExpiresAt()->format('c'),
            'createdAt' => $t->getCreatedAt()->format('c'),
            'filesCount' => $t->getFiles()->count(),
            'totalSize' => $t->getTotalFilesSize(),
            'recipientsCount' => $t->getRecipients()->count(),
            'downloadedCount' => $t->getRecipients()->filter(fn ($r): bool => $r->hasDownloaded())->count(),
            'isPasswordProtected' => $t->isPasswordProtected(),
        ];
    }
}
