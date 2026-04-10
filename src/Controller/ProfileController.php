<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function edit(): Response
    {
        return $this->render('profile/edit.html.twig');
    }

    #[Route('/profile/update', name: 'app_profile_update', methods: ['POST'])]
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
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserManager $userManager,
        UserPasswordHasherInterface $hasher,
        TranslatorInterface $translator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];

        $body = json_decode($request->getContent(), true) ?? [];
        $current = $body['current_password'] ?? '';
        $new = $body['password'] ?? '';
        $confirm = $body['password_confirmation'] ?? '';

        if (!$hasher->isPasswordValid($user, $current)) {
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

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        UserManager $userManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $body = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('delete_account', $body['_token'] ?? '')) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $userManager->delete($user);

        return new JsonResponse(['success' => true, 'redirectUrl' => $this->generateUrl('app_login')]);
    }
}
