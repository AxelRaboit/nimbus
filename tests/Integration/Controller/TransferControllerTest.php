<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Transfer;
use App\Enum\TransferStatusEnum;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class TransferControllerTest extends IntegrationTestCase
{
    public function testShowReadyTransferIsAccessible(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyUnprotectedTransfer($em);

        $client->request('GET', '/t/'.$transfer->getToken());

        self::assertResponseIsSuccessful();
    }

    public function testShowPasswordProtectedTransferRendersPasswordPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $this->findReadyPasswordProtectedTransfer($em);

        $client->request('GET', '/t/'.$transfer->getToken());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#app-transfer-password');
    }

    public function testShowExpiredTransferRendersUnavailable(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $transfer = $em->getRepository(Transfer::class)->findOneBy(['status' => TransferStatusEnum::Expired]);
        self::assertNotNull($transfer, 'No expired transfer found in fixtures');

        $client->request('GET', '/t/'.$transfer->getToken());

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

    private function findReadyUnprotectedTransfer(EntityManagerInterface $em): Transfer
    {
        $transfers = $em->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if (!$transfer->isPasswordProtected()) {
                return $transfer;
            }
        }

        self::fail('No ready unprotected transfer found in fixtures');
    }

    private function findReadyPasswordProtectedTransfer(EntityManagerInterface $em): Transfer
    {
        $transfers = $em->getRepository(Transfer::class)->findBy(['status' => TransferStatusEnum::Ready]);

        foreach ($transfers as $transfer) {
            if ($transfer->isPasswordProtected()) {
                return $transfer;
            }
        }

        self::fail('No ready password-protected transfer found in fixtures');
    }
}
