<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Message\EmailQueueMessage;
use App\Service\TransferNotifier;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

final class TransferNotifierTest extends TestCase
{
    public function testNotifyReadyDispatchesOneMessagePerRecipient(): void
    {
        $recipient1 = $this->createStub(Recipient::class);
        $recipient1->method('getEmail')->willReturn('r1@example.com');
        $recipient1->method('getToken')->willReturn('token-1');

        $recipient2 = $this->createStub(Recipient::class);
        $recipient2->method('getEmail')->willReturn('r2@example.com');
        $recipient2->method('getToken')->willReturn('token-2');

        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getRecipients')->willReturn(new ArrayCollection([$recipient1, $recipient2]));
        $transfer->method('getUser')->willReturn(null);
        $transfer->method('getSenderName')->willReturn('Alice');
        $transfer->method('getSenderEmail')->willReturn('alice@example.com');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::exactly(2))
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailQueueMessage::class))
            ->willReturn(new Envelope(new stdClass()));

        $this->buildNotifier(messageBus: $messageBus)->notifyReady($transfer);
    }

    public function testNotifyReadyDispatchesNoMessagesWhenNoRecipients(): void
    {
        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getRecipients')->willReturn(new ArrayCollection());

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $this->buildNotifier(messageBus: $messageBus)->notifyReady($transfer);
    }

    public function testNotifyDownloadedSkipsWhenNoSenderEmail(): void
    {
        $recipient = $this->createStub(Recipient::class);
        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getSenderEmail')->willReturn(null);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $this->buildNotifier(messageBus: $messageBus)->notifyDownloaded($transfer, $recipient);
    }

    public function testNotifyDownloadedDispatchesMessageToSender(): void
    {
        $recipient = $this->createStub(Recipient::class);
        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getSenderEmail')->willReturn('sender@example.com');
        $transfer->method('getUser')->willReturn(null);
        $transfer->method('getReference')->willReturn('REF-001');

        $capturedMessage = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (EmailQueueMessage $message) use (&$capturedMessage): Envelope {
                $capturedMessage = $message;

                return new Envelope($message);
            });

        $this->buildNotifier(messageBus: $messageBus)->notifyDownloaded($transfer, $recipient);

        self::assertNotNull($capturedMessage);
        self::assertSame('sender@example.com', $capturedMessage->getRecipientEmail());
    }

    public function testNotifyExpiredSkipsWhenNoSenderEmail(): void
    {
        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getSenderEmail')->willReturn(null);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $this->buildNotifier(messageBus: $messageBus)->notifyExpired($transfer);
    }

    public function testNotifyExpiredDispatchesMessageToSender(): void
    {
        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getSenderEmail')->willReturn('sender@example.com');
        $transfer->method('getUser')->willReturn(null);
        $transfer->method('getReference')->willReturn('REF-001');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailQueueMessage::class))
            ->willReturn(new Envelope(new stdClass()));

        $this->buildNotifier(messageBus: $messageBus)->notifyExpired($transfer);
    }

    public function testNotifyReminderDispatchesMessageToRecipient(): void
    {
        $recipient = $this->createStub(Recipient::class);
        $recipient->method('getEmail')->willReturn('recipient@example.com');
        $recipient->method('getToken')->willReturn('dl-token');

        $transfer = $this->createStub(Transfer::class);
        $transfer->method('getUser')->willReturn(null);
        $transfer->method('getReference')->willReturn('REF-001');

        $capturedMessage = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (EmailQueueMessage $message) use (&$capturedMessage): Envelope {
                $capturedMessage = $message;

                return new Envelope($message);
            });

        $this->buildNotifier(messageBus: $messageBus)->notifyReminder($transfer, $recipient);

        self::assertNotNull($capturedMessage);
        self::assertSame('recipient@example.com', $capturedMessage->getRecipientEmail());
    }

    private function buildNotifier(
        ?MessageBusInterface $messageBus = null,
        ?TwigEnvironment $twig = null,
        ?UrlGeneratorInterface $urlGenerator = null,
        ?TranslatorInterface $translator = null,
        ?LocaleSwitcher $localeSwitcher = null,
    ): TransferNotifier {
        if (null === $twig) {
            $twig = $this->createStub(TwigEnvironment::class);
            $twig->method('render')->willReturn('<p>email</p>');
        }

        if (null === $translator) {
            $translator = $this->createStub(TranslatorInterface::class);
            $translator->method('trans')->willReturn('Subject');
        }

        if (null === $localeSwitcher) {
            $localeSwitcher = $this->createStub(LocaleSwitcher::class);
            $localeSwitcher->method('runWithLocale')->willReturnCallback(
                static fn (string $locale, Closure $callback): mixed => $callback(),
            );
        }

        return new TransferNotifier(
            $messageBus ?? $this->createStub(MessageBusInterface::class),
            $twig,
            $urlGenerator ?? $this->createStub(UrlGeneratorInterface::class),
            $translator,
            $localeSwitcher,
        );
    }
}
