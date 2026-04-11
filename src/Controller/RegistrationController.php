<?php

declare(strict_types=1);

namespace App\Controller;

use App\Manager\UserManager;
use App\Repository\ApplicationParameterRepository;
use App\Service\EmailValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserManager $userManager,
        TranslatorInterface $translator,
        Security $security,
        ApplicationParameterRepository $params,
    ): Response {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        if ('0' === $params->get('registration_enabled', '1')) {
            return $this->render('registration/register.html.twig', [
                'registrationEnabled' => false,
                'errors' => [],
                'values' => [],
            ]);
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $name = mb_trim($request->request->get('name', ''));
            $email = mb_trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirmation', '');

            if ('' === $name || '0' === $name) {
                $errors['name'] = $translator->trans('auth.register.error_name_required');
            }

            if ('' === $email || '0' === $email) {
                $errors['email'] = $translator->trans('auth.register.error_email_required');
            } elseif (!EmailValidator::isValid($email)) {
                $errors['email'] = $translator->trans('auth.register.error_email_invalid');
            } elseif ($userManager->isEmailTaken($email)) {
                $errors['email'] = $translator->trans('auth.register.error_email_taken');
            }

            if (mb_strlen($password) < 8) {
                $errors['password'] = $translator->trans('auth.register.error_password_length');
            } elseif ($password !== $passwordConfirm) {
                $errors['password_confirmation'] = $translator->trans('auth.register.error_password_mismatch');
            }

            if ([] === $errors) {
                $user = $userManager->create($name, $email, $password);
                $security->login($user, 'form_login', 'main');

                return $this->redirectToRoute('home');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationEnabled' => true,
            'errors' => $errors,
            'values' => $request->request->all(),
        ]);
    }
}
