<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\TransferStatusEnum;
use App\Message\SendRemindersMessage;
use App\Repository\RecipientRepository;
use App\Service\TransferNotifierInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendRemindersHandler
{
    public function __construct(
        private RecipientRepository $recipientRepository,
        private TransferNotifierInterface $notifier,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SendRemindersMessage $message): void
    {
        $now = new DateTimeImmutable();

        // Candidates: not downloaded, not yet reminded, transfer ready and not expired
        $recipients = $this->recipientRepository->createQueryBuilder('r')
            ->join('r.transfer', 't')
            ->where('r.downloadedAt IS NULL')
            ->andWhere('r.lastReminderSentAt IS NULL')
            ->andWhere('t.status = :status')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('status', TransferStatusEnum::Ready)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($recipients as $recipient) {
            $transfer = $recipient->getTransfer();
            $createdAt = $transfer->getCreatedAt()->getTimestamp();
            $expiresAt = $transfer->getExpiresAt()->getTimestamp();
            $midpoint = $createdAt + (int) (($expiresAt - $createdAt) / 2);

            if ($now->getTimestamp() < $midpoint) {
                continue;
            }

            $this->notifier->notifyReminder($transfer, $recipient);
            $recipient->setLastReminderSentAt($now);
            ++$count;
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info('Sent {count} reminder(s) to pending recipients.', ['count' => $count]);
        }
    }
}
