<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Contract\UserManagerInterface;
use App\DTO\PaginationRequest;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/users')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class UsersController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManagerInterface $userManager,
    ) {}

    #[Route('', name: 'dev_users')]
    public function index(PaginationRequest $pagination): Response
    {
        $result = $this->userRepository->findPaginatedForAdmin($pagination->page, $pagination->search);

        return $this->render('dev/index.html.twig', [
            'tab' => 'users',
            'users' => [
                'items' => array_map($this->serialize(...), $result['items']),
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
            ],
            'search' => $pagination->search ?? '',
        ]);
    }

    #[Route('/{id}/delete', name: 'dev_user_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('dev_users');
        }

        $this->userManager->delete($user);

        return $this->redirectToRoute('dev_users');
    }

    #[Route('/{id}/custom-file-size', name: 'dev_user_custom_file_size', methods: [HttpMethodEnum::Post->value])]
    public function updateCustomFileSize(User $user, Request $request): Response
    {
        $raw = $request->request->get('custom_file_size_mb', '');
        $user->setCustomFileSizeMb('' !== $raw ? (abs((int) $raw) ?: null) : null);
        $this->userManager->save($user);

        return $this->redirectToRoute('dev_users');
    }

    #[Route('/{id}/toggle-role', name: 'dev_user_toggle_role', methods: [HttpMethodEnum::Post->value])]
    public function toggleRole(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');

            return $this->redirectToRoute('dev_users');
        }

        $this->userManager->toggleDevRole($user);

        return $this->redirectToRoute('dev_users');
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'plan' => $user->getPlan()->value,
            'isDevRole' => in_array(UserRoleEnum::Dev->value, $user->getRoles(), true),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'trialEndsAt' => $user->getTrialEndsAt()?->format('c'),
            'customFileSizeMb' => $user->getCustomFileSizeMb(),
        ];
    }
}
