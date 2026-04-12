<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Message\SendRemindersMessage;
use App\MessageHandler\SendRemindersHandler;
use App\Repository\RecipientRepository;
use App\Service\TransferNotifierInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;

final class SendRemindersHandlerTest extends TestCase
{
    public function testSkipsRecipientsBeforeMidpoint(): void
    {
        // Created 1h ago, expires in 10h → total 11h, midpoint at 5.5h ago → not reached yet
        $transfer = $this->makeTransfer(createdHoursAgo: 1, expiresInHours: 10);
        $recipient = $this->makeRecipient($transfer);

        $repo = $this->createStub(RecipientRepository::class);
        $repo->method('findPendingUnreminded')->willReturn([$recipient]);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::never())->method('notifyReminder');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        ($this->buildHandler(repo: $repo, notifier: $notifier, entityManager: $entityManager))(new SendRemindersMessage());
    }

    public function testSendsReminderAfterMidpoint(): void
    {
        // Created 6h ago, expires in 4h → total 10h, midpoint at 5h ago → already passed
        $transfer = $this->makeTransfer(createdHoursAgo: 6, expiresInHours: 4);
        $recipient = $this->makeRecipient($transfer);

        $repo = $this->createStub(RecipientRepository::class);
        $repo->method('findPendingUnreminded')->willReturn([$recipient]);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReminder')->with($transfer, $recipient);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        ($this->buildHandler(repo: $repo, notifier: $notifier, entityManager: $entityManager))(new SendRemindersMessage());

        self::assertNotNull($recipient->getLastReminderSentAt());
    }

    public function testSendsRemindersOnlyToEligibleRecipients(): void
    {
        // First: midpoint not reached → skip
        $earlyTransfer = $this->makeTransfer(createdHoursAgo: 1, expiresInHours: 10);
        $earlyRecipient = $this->makeRecipient($earlyTransfer);

        // Second: midpoint passed → send
        $lateTransfer = $this->makeTransfer(createdHoursAgo: 6, expiresInHours: 4);
        $lateRecipient = $this->makeRecipient($lateTransfer);

        $repo = $this->createStub(RecipientRepository::class);
        $repo->method('findPendingUnreminded')->willReturn([$earlyRecipient, $lateRecipient]);

        $notifier = $this->createMock(TransferNotifierInterface::class);
        $notifier->expects(self::once())->method('notifyReminder')->with($lateTransfer, $lateRecipient);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        ($this->buildHandler(repo: $repo, notifier: $notifier, entityManager: $entityManager))(new SendRemindersMessage());
    }

    public function testDoesNotFlushWhenNoRemindersSent(): void
    {
        $repo = $this->createStub(RecipientRepository::class);
        $repo->method('findPendingUnreminded')->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        ($this->buildHandler(repo: $repo, entityManager: $entityManager))(new SendRemindersMessage());
    }

    private function buildHandler(
        ?RecipientRepository $repo = null,
        ?TransferNotifierInterface $notifier = null,
        ?EntityManagerInterface $entityManager = null,
    ): SendRemindersHandler {
        return new SendRemindersHandler(
            $repo ?? $this->createStub(RecipientRepository::class),
            $notifier ?? $this->createStub(TransferNotifierInterface::class),
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            new NullLogger(),
        );
    }

    private function makeTransfer(int $createdHoursAgo, int $expiresInHours): Transfer
    {
        $transfer = new Transfer();
        $transfer->setCreatedAtValue();

        $prop = new ReflectionProperty(Transfer::class, 'createdAt');
        $prop->setValue($transfer, new DateTimeImmutable(sprintf('-%d hours', $createdHoursAgo)));

        $transfer->setExpiresAt(new DateTimeImmutable(sprintf('+%d hours', $expiresInHours)));

        return $transfer;
    }

    private function makeRecipient(Transfer $transfer): Recipient
    {
        $recipient = new Recipient();
        $recipient->setEmail('test@example.com');
        $recipient->setTransfer($transfer);

        return $recipient;
    }
}
