<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Service\PasswordResetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(
        Request $request,
        PasswordResetManager $passwordResetManager,
        TranslatorInterface $translator,
    ): Response {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $status = null;

        if ($request->isMethod('POST')) {
            $email = mb_trim($request->request->get('email', ''));
            $passwordResetManager->sendResetLink($email);
            $status = $translator->trans('auth.forgot_password.sent');
        }

        return $this->render('security/forgot_password.html.twig', [
            'status' => $status,
            'error' => null,
        ]);
    }

    #[Route('/reset-password/{selector}/{token}', name: 'app_reset_password')]
    public function reset(
        string $selector,
        string $token,
        Request $request,
        PasswordResetManager $passwordResetManager,
        TranslatorInterface $translator,
    ): Response {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $resetRequest = $passwordResetManager->validateToken($selector, $token);

        if (!$resetRequest instanceof ResetPasswordRequest) {
            $this->addFlash('error', $translator->trans('auth.reset_password.invalid_link'));

            return $this->redirectToRoute('app_forgot_password');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirm = $request->request->get('password_confirmation', '');

            if (mb_strlen($password) < 8) {
                $errors['password'] = $translator->trans('auth.register.error_password_length');
            } elseif ($password !== $confirm) {
                $errors['password_confirmation'] = $translator->trans('auth.register.error_password_mismatch');
            }

            if ([] === $errors) {
                $passwordResetManager->resetPassword($resetRequest, $password);

                return $this->redirectToRoute('app_login', ['reset' => 1]);
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'errors' => $errors,
            'selector' => $selector,
            'token' => $token,
        ]);
    }
}
