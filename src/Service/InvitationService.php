<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\EmailTypeEnum;
use App\Message\EmailQueueMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment as TwigEnvironment;

final readonly class InvitationService
{
    public function __construct(
        private TwigEnvironment $twig,
        private MessageBusInterface $messageBus,
    ) {}

    public function send(
        string $email,
        string $message = '',
        string $credentialEmail = '',
        string $credentialPassword = '',
    ): void {
        $body = $this->twig->render('email/invitation.html.twig', [
            'customMessage' => $message ?: null,
            'credentialEmail' => $credentialEmail ?: null,
            'credentialPassword' => $credentialPassword ?: null,
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            EmailTypeEnum::Invitation->value,
            $email,
            'Vous êtes invité sur Nimbus',
            $body,
        ));
    }
}
