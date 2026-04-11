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
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyPublicTransfer($em);

        $client->request('GET', '/t/'.$transfer->getToken());

        self::assertResponseIsSuccessful();
    }

    public function testShowPrivateTransferViaTransferTokenReturns404(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyUnprotectedTransfer($em);

        $client->request('GET', '/t/'.$transfer->getToken());

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowReadyTransferViaRecipientTokenIsAccessible(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $recipient = $this->findRecipientForReadyUnprotectedTransfer($em);

        $client->request('GET', '/t/'.$recipient->getToken());

        self::assertResponseIsSuccessful();
    }

    public function testShowPasswordProtectedTransferRendersPasswordPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $recipient = $this->findRecipientForReadyPasswordProtectedTransfer($em);

        $client->request('GET', '/t/'.$recipient->getToken());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#app-transfer-password');
    }

    public function testShowExpiredTransferRendersUnavailable(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $em->getRepository(Transfer::class)->findOneBy(['status' => TransferStatusEnum::Expired]);
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
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyUnprotectedTransfer($em);

        $client->request('GET', '/manage/'.$transfer->getOwnerToken());

        self::assertResponseIsSuccessful();
    }

    public function testManagePageForNonExistentOwnerTokenReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manage/nonexistent-owner-token');

        self::assertResponseStatusCodeSame(404);
    }

    private function findReadyPublicTransfer(EntityManagerInterface $em): Transfer
    {
        $transfers = $em->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if ($transfer->isPublic()) {
                return $transfer;
            }
        }

        self::fail('No ready public transfer found in fixtures');
    }

    private function findReadyUnprotectedTransfer(EntityManagerInterface $em): Transfer
    {
        $transfers = $em->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if (!$transfer->isPasswordProtected() && !$transfer->isPublic()) {
                return $transfer;
            }
        }

        self::fail('No ready unprotected private transfer found in fixtures');
    }

    private function findRecipientForReadyUnprotectedTransfer(EntityManagerInterface $em): Recipient
    {
        $transfer = $this->findReadyUnprotectedTransfer($em);
        $recipient = $transfer->getRecipients()->first();
        self::assertNotFalse($recipient, 'No recipient found for ready unprotected transfer');

        return $recipient;
    }

    private function findRecipientForReadyPasswordProtectedTransfer(EntityManagerInterface $em): Recipient
    {
        $transfers = $em->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

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
