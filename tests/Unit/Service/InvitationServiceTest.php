<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\EmailTypeEnum;
use App\Message\EmailQueueMessage;
use App\Service\InvitationService;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

final class InvitationServiceTest extends TestCase
{
    public function testSendDispatchesEmailQueueMessage(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailQueueMessage::class))
            ->willReturn(new Envelope(new stdClass()));

        $twig = $this->createStub(TwigEnvironment::class);
        $twig->method('render')->willReturn('<p>Invitation</p>');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/login');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('You are invited');

        $service = new InvitationService($twig, $messageBus, $urlGenerator, $translator);
        $service->send('recipient@example.com', 'Welcome!');
    }

    public function testSendDispatchesWithCorrectRecipientEmail(): void
    {
        $capturedMessage = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (EmailQueueMessage $message) use (&$capturedMessage): Envelope {
                $capturedMessage = $message;

                return new Envelope($message);
            });

        $twig = $this->createStub(TwigEnvironment::class);
        $twig->method('render')->willReturn('<p>body</p>');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/login');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Subject');

        $service = new InvitationService($twig, $messageBus, $urlGenerator, $translator);
        $service->send('invited@example.com', '', 'cred@example.com', 'secret');

        self::assertNotNull($capturedMessage);
        self::assertSame('invited@example.com', $capturedMessage->getRecipientEmail());
        self::assertSame(EmailTypeEnum::Invitation->value, $capturedMessage->getType());
    }

    public function testSendPassesCredentialsToTemplate(): void
    {
        $capturedContext = null;

        $twig = $this->createMock(TwigEnvironment::class);
        $twig->expects(self::once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $context) use (&$capturedContext): string {
                $capturedContext = $context;

                return '<p>body</p>';
            });

        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/login');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Subject');

        $service = new InvitationService($twig, $messageBus, $urlGenerator, $translator);
        $service->send('recipient@example.com', 'Hi there', 'admin@example.com', 'pass123');

        self::assertSame('Hi there', $capturedContext['customMessage']);
        self::assertSame('admin@example.com', $capturedContext['credentialEmail']);
        self::assertSame('pass123', $capturedContext['credentialPassword']);
    }
}
