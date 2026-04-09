<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\EmailQueueMessage;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class EmailQueueMessageHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerSender,
    ) {}

    public function __invoke(EmailQueueMessage $message): void
    {
        try {
            $email = (new Email())
                ->from($this->mailerSender)
                ->to($message->getRecipientEmail())
                ->subject(sprintf('[Nimbus] %s', $message->getSubject()))
                ->html($message->getBody());

            $this->mailer->send($email);

            $this->logger->info('Email sent via Messenger', [
                'type' => $message->getType(),
                'recipient' => $message->getRecipientEmail(),
            ]);
        } catch (Exception $exception) {
            if ($exception instanceof TransportExceptionInterface) {
                $this->logger->warning('Email transport error (will retry)', [
                    'type' => $message->getType(),
                    'recipient' => $message->getRecipientEmail(),
                    'error' => $exception->getMessage(),
                ]);

                throw new RejectRedeliveredMessageException(sprintf('Email transport error: %s', $exception->getMessage()), 0, $exception);
            }

            $this->logger->error('Failed to send email via Messenger', [
                'type' => $message->getType(),
                'recipient' => $message->getRecipientEmail(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
