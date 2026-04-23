<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Enum\TransferStatusEnum;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class TransferControllerTest extends IntegrationTestCase
{
    public function testShowPublicTransferViaTransferTokenIsAccessible(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyPublicTransfer($entityManager);

        $client->request('GET', '/t/'.$transfer->getToken());

        self::assertResponseIsSuccessful();
    }

    public function testShowPrivateTransferViaTransferTokenReturns404(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyUnprotectedTransfer($entityManager);

        $client->request('GET', '/t/'.$transfer->getToken());

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowReadyTransferViaRecipientTokenIsAccessible(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $recipient = $this->findRecipientForReadyUnprotectedTransfer($entityManager);

        $client->request('GET', '/t/'.$recipient->getToken());

        self::assertResponseIsSuccessful();
    }

    public function testShowPasswordProtectedTransferRendersPasswordPage(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $recipient = $this->findRecipientForReadyPasswordProtectedTransfer($entityManager);

        $client->request('GET', '/t/'.$recipient->getToken());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-symfony--ux-vue--vue-component-value="TransferPasswordApp"]');
    }

    public function testShowExpiredTransferRendersUnavailable(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $entityManager->getRepository(Transfer::class)->findOneBy(['status' => TransferStatusEnum::Expired]);
        self::assertNotNull($transfer, 'No expired transfer found in fixtures');
        $recipient = $transfer->getRecipients()->first();
        self::assertNotFalse($recipient, 'No recipient found for expired transfer');

        $client->request('GET', '/t/'.$recipient->getToken());

        self::assertResponseIsSuccessful();
        self::assertRouteSame('transfer_show');
    }

    public function testShowNonExistentTransferReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/t/nonexistent-token');

        self::assertResponseStatusCodeSame(404);
    }

    public function testManagePageIsAccessible(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyUnprotectedTransfer($entityManager);

        $client->request('GET', '/manage/'.$transfer->getOwnerToken());

        self::assertResponseIsSuccessful();
    }

    public function testManagePageForNonExistentOwnerTokenReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manage/nonexistent-owner-token');

        self::assertResponseStatusCodeSame(404);
    }

    private function findReadyPublicTransfer(EntityManagerInterface $entityManager): Transfer
    {
        $transfers = $entityManager->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if ($transfer->isPublic()) {
                return $transfer;
            }
        }

        self::fail('No ready public transfer found in fixtures');
    }

    private function findReadyUnprotectedTransfer(EntityManagerInterface $entityManager): Transfer
    {
        $transfers = $entityManager->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if (!$transfer->isPasswordProtected() && !$transfer->isPublic()) {
                return $transfer;
            }
        }

        self::fail('No ready unprotected private transfer found in fixtures');
    }

    private function findRecipientForReadyUnprotectedTransfer(EntityManagerInterface $entityManager): Recipient
    {
        $transfer = $this->findReadyUnprotectedTransfer($entityManager);
        $recipient = $transfer->getRecipients()->first();
        self::assertNotFalse($recipient, 'No recipient found for ready unprotected transfer');

        return $recipient;
    }

    private function findRecipientForReadyPasswordProtectedTransfer(EntityManagerInterface $entityManager): Recipient
    {
        $transfers = $entityManager->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if ($transfer->isPasswordProtected()) {
                $recipient = $transfer->getRecipients()->first();
                if ($recipient) {
                    return $recipient;
                }
            }
        }

        self::fail('No ready password-protected transfer with recipient found in fixtures');
    }
}
