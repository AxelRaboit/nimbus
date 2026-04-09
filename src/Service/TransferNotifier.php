<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Enum\EmailTypeEnum;
use App\Message\EmailQueueMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

final readonly class TransferNotifier
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function notifyReady(Transfer $transfer): void
    {
        foreach ($transfer->getRecipients() as $recipient) {
            $this->sendTransferReadyEmail($transfer, $recipient);
        }
    }

    public function notifyDownloaded(Transfer $transfer, Recipient $recipient): void
    {
        $senderEmail = $transfer->getSenderEmail();
        if (null === $senderEmail) {
            return;
        }

        $body = $this->twig->render('email/transfer_downloaded.html.twig', [
            'transfer' => $transfer,
            'recipient' => $recipient,
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferDownloaded->value,
            recipientEmail: $senderEmail,
            subject: sprintf('Vos fichiers ont été téléchargés [Réf. %s]', $transfer->getReference()),
            body: $body,
        ));
    }

    public function notifyExpired(Transfer $transfer): void
    {
        $senderEmail = $transfer->getSenderEmail();
        if (null === $senderEmail) {
            return;
        }

        $body = $this->twig->render('email/transfer_expired.html.twig', [
            'transfer' => $transfer,
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferExpired->value,
            recipientEmail: $senderEmail,
            subject: sprintf('Votre transfert a expiré [Réf. %s]', $transfer->getReference()),
            body: $body,
        ));
    }

    private function sendTransferReadyEmail(Transfer $transfer, Recipient $recipient): void
    {
        $downloadUrl = $this->urlGenerator->generate(
            'transfer_show',
            ['token' => $recipient->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $body = $this->twig->render('email/transfer_ready.html.twig', [
            'transfer' => $transfer,
            'recipient' => $recipient,
            'downloadUrl' => $downloadUrl,
        ]);

        $senderName = $transfer->getSenderName() ?? $transfer->getSenderEmail() ?? "Quelqu'un";

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferReady->value,
            recipientEmail: $recipient->getEmail(),
            subject: sprintf('%s vous a envoyé des fichiers [Réf. %s]', $senderName, $transfer->getReference()),
            body: $body,
        ));
    }
}
