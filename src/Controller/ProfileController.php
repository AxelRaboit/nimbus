<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Manager\UserManager;
use App\Service\EmailValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(UserRoleEnum::User->value)]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function edit(): Response
    {
        return $this->render('profile/edit.html.twig');
    }

    #[Route('/profile/update', name: 'profile_update', methods: [HttpMethodEnum::Post->value])]
    public function update(
        Request $request,
        UserManager $userManager,
        TranslatorInterface $translator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];

        $body = json_decode($request->getContent(), true) ?? [];
        $name = mb_trim($body['name'] ?? '');
        $email = mb_trim($body['email'] ?? '');

        if ('' === $name || '0' === $name) {
            $errors['name'] = $translator->trans('auth.register.error_name_required');
        }

        if ('' === $email || '0' === $email) {
            $errors['email'] = $translator->trans('auth.register.error_email_required');
        } elseif (!EmailValidator::isValid($email)) {
            $errors['email'] = $translator->trans('auth.register.error_email_invalid');
        } elseif ($userManager->isEmailTaken($email, $user)) {
            $errors['email'] = $translator->trans('auth.register.error_email_taken');
        }

        if ([] === $errors) {
            $userManager->update($user, $name, $email);

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['errors' => $errors]);
    }

    #[Route('/profile/password', name: 'profile_password', methods: [HttpMethodEnum::Post->value])]
    public function changePassword(
        Request $request,
        UserManager $userManager,
        TranslatorInterface $translator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];

        $body = json_decode($request->getContent(), true) ?? [];
        $current = $body['current_password'] ?? '';
        $new = $body['password'] ?? '';
        $confirm = $body['password_confirmation'] ?? '';

        if (!$userManager->isPasswordValid($user, $current)) {
            $errors['current_password'] = $translator->trans('profile.password.error_current');
        } elseif (mb_strlen((string) $new) < 8) {
            $errors['password'] = $translator->trans('auth.register.error_password_length');
        } elseif ($new !== $confirm) {
            $errors['password_confirmation'] = $translator->trans('auth.register.error_password_mismatch');
        }

        if ([] === $errors) {
            $userManager->changePassword($user, $new);

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['errors' => $errors]);
    }

    #[Route('/profile/delete', name: 'profile_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(
        Request $request,
        UserManager $userManager,
        TranslatorInterface $translator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $body = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('delete_account', $body['_token'] ?? '')) {
            throw $this->createAccessDeniedException($translator->trans('error.csrf_invalid'));
        }

        $userManager->delete($user);

        return new JsonResponse(['success' => true, 'redirectUrl' => $this->generateUrl('app_login')]);
    }
}
