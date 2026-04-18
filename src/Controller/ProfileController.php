<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\UserManagerInterface;
use App\Controller\Trait\JsonValidationTrait;
use App\DTO\ChangePasswordInput;
use App\DTO\UpdateProfileInput;
use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(UserRoleEnum::User->value)]
final class ProfileController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/profile', name: 'profile')]
    public function edit(): Response
    {
        return $this->render('profile/edit.html.twig');
    }

    #[Route('/profile/update', name: 'profile_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $input = UpdateProfileInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)]);
        }

        $this->userManager->update($user, $input->name, $input->email);

        return $this->json(['success' => true]);
    }

    #[Route('/profile/password', name: 'profile_password', methods: [HttpMethodEnum::Post->value])]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $input = ChangePasswordInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)]);
        }

        if (!$this->userManager->isPasswordValid($user, $input->currentPassword)) {
            return $this->json(['errors' => [
                'currentPassword' => $this->translator->trans('profile.password.error_current'),
            ]]);
        }

        if ($input->password !== $input->passwordConfirmation) {
            return $this->json(['errors' => [
                'password_confirmation' => $this->translator->trans('auth.register.error_password_mismatch'),
            ]]);
        }

        $this->userManager->changePassword($user, $input->password);

        return $this->json(['success' => true]);
    }

    #[Route('/profile/delete', name: 'profile_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $body = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('delete_account', $body['_token'] ?? '')) {
            throw $this->createAccessDeniedException($this->translator->trans('error.csrf_invalid'));
        }

        $this->userManager->delete($user);

        return $this->json(['success' => true, 'redirectUrl' => $this->generateUrl('app_login')]);
    }
}
