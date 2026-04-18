<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\PasswordResetManagerInterface;
use App\DTO\ResetPasswordInput;
use App\Entity\ResetPasswordRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetManagerInterface $passwordResetManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $status = null;

        if ($request->isMethod('POST')) {
            $email = mb_trim($request->request->get('email', ''));
            $this->passwordResetManager->sendResetLink($email);
            $status = $this->translator->trans('auth.forgot_password.sent');
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
    ): Response {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $resetRequest = $this->passwordResetManager->validateToken($selector, $token);

        if (!$resetRequest instanceof ResetPasswordRequest) {
            $this->addFlash('error', $this->translator->trans('auth.reset_password.invalid_link'));

            return $this->redirectToRoute('app_forgot_password');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $input = ResetPasswordInput::fromRequest($request);

            $violations = $this->validator->validate($input);
            foreach ($violations as $violation) {
                $field = $violation->getPropertyPath();
                if (!isset($errors[$field])) {
                    $errors[$field] = $violation->getMessage();
                }
            }

            if (!isset($errors['password']) && $input->password !== $input->passwordConfirmation) {
                $errors['password_confirmation'] = $this->translator->trans('auth.register.error_password_mismatch');
            }

            if ([] === $errors) {
                $this->passwordResetManager->resetPassword($resetRequest, $input->password);

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
