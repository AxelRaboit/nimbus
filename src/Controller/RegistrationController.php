<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\UserManagerInterface;
use App\DTO\RegisterInput;
use App\Repository\ApplicationParameterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
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
            $input = RegisterInput::fromRequest($request);

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
                $user = $this->userManager->create($input->name, $input->email, $input->password);
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
