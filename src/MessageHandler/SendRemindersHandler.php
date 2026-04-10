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
        $reminderThreshold = new DateTimeImmutable('-2 days');

        // Find recipients who have not downloaded yet and whose transfer is still ready,
        // and who have not been reminded in the last 2 days (or never been reminded)
        $recipients = $this->recipientRepository->createQueryBuilder('r')
            ->join('r.transfer', 't')
            ->where('r.downloadedAt IS NULL')
            ->andWhere('t.status = :status')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('r.lastReminderSentAt IS NULL OR r.lastReminderSentAt < :threshold')
            ->setParameter('status', TransferStatusEnum::Ready)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('threshold', $reminderThreshold)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($recipients as $recipient) {
            $this->notifier->notifyReminder($recipient->getTransfer(), $recipient);
            $recipient->setLastReminderSentAt(new DateTimeImmutable());
            ++$count;
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info('Sent {count} reminder(s) to pending recipients.', ['count' => $count]);
        }
    }
}
