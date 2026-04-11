<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Enum\EmailTypeEnum;
use App\Enum\LocaleEnum;
use App\Message\EmailQueueMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

final readonly class TransferNotifier implements TransferNotifierInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private LocaleSwitcher $localeSwitcher,
    ) {}

    public function notifyReady(Transfer $transfer, ?string $plainPassword = null): void
    {
        foreach ($transfer->getRecipients() as $recipient) {
            $this->sendTransferReadyEmail($transfer, $recipient, $plainPassword);
        }
    }

    public function notifyDownloaded(Transfer $transfer, Recipient $recipient): void
    {
        $senderEmail = $transfer->getSenderEmail();
        if (null === $senderEmail) {
            return;
        }

        $locale = $transfer->getUser()?->getLocale() ?? LocaleEnum::default();

        [$body, $subject] = $this->localeSwitcher->runWithLocale($locale->value, fn (): array => [
            $this->twig->render('email/transfer_downloaded.html.twig', [
                'transfer' => $transfer,
                'recipient' => $recipient,
            ]),
            $this->translator->trans('mail.transfer_downloaded.subject', [
                '%reference%' => $transfer->getReference(),
            ]),
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferDownloaded->value,
            recipientEmail: $senderEmail,
            subject: $subject,
            body: $body,
        ));
    }

    public function notifyExpired(Transfer $transfer): void
    {
        $senderEmail = $transfer->getSenderEmail();
        if (null === $senderEmail) {
            return;
        }

        $locale = $transfer->getUser()?->getLocale() ?? LocaleEnum::default();

        [$body, $subject] = $this->localeSwitcher->runWithLocale($locale->value, fn (): array => [
            $this->twig->render('email/transfer_expired.html.twig', [
                'transfer' => $transfer,
            ]),
            $this->translator->trans('mail.transfer_expired.subject', [
                '%reference%' => $transfer->getReference(),
            ]),
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferExpired->value,
            recipientEmail: $senderEmail,
            subject: $subject,
            body: $body,
        ));
    }

    public function notifyReminder(Transfer $transfer, Recipient $recipient): void
    {
        $downloadUrl = $this->urlGenerator->generate(
            'transfer_show',
            ['token' => $recipient->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $locale = $transfer->getUser()?->getLocale() ?? LocaleEnum::default();

        [$body, $subject] = $this->localeSwitcher->runWithLocale($locale->value, fn (): array => [
            $this->twig->render('email/transfer_reminder.html.twig', [
                'transfer' => $transfer,
                'recipient' => $recipient,
                'downloadUrl' => $downloadUrl,
            ]),
            $this->translator->trans('mail.transfer_reminder.subject', [
                '%reference%' => $transfer->getReference(),
            ]),
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferReminder->value,
            recipientEmail: $recipient->getEmail(),
            subject: $subject,
            body: $body,
        ));
    }

    private function sendTransferReadyEmail(Transfer $transfer, Recipient $recipient, ?string $plainPassword = null): void
    {
        $downloadUrl = $this->urlGenerator->generate(
            'transfer_show',
            ['token' => $recipient->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $locale = $transfer->getUser()?->getLocale() ?? LocaleEnum::default();
        $senderName = $transfer->getSenderName() ?? $transfer->getSenderEmail() ?? 'Nimbus';

        [$body, $subject] = $this->localeSwitcher->runWithLocale($locale->value, fn (): array => [
            $this->twig->render('email/transfer_ready.html.twig', [
                'transfer' => $transfer,
                'recipient' => $recipient,
                'downloadUrl' => $downloadUrl,
                'plainPassword' => $plainPassword,
            ]),
            $this->translator->trans('mail.transfer_ready.subject', [
                '%senderName%' => $senderName,
            ]),
        ]);

        $this->messageBus->dispatch(new EmailQueueMessage(
            type: EmailTypeEnum::TransferReady->value,
            recipientEmail: $recipient->getEmail(),
            subject: $subject,
            body: $body,
        ));
    }
}
