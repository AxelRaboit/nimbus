<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\TransferStats;
use App\Repository\RecipientRepository;
use App\Repository\TransferFileRepository;
use App\Repository\TransferRepository;
use App\Repository\TransferStatsRepository;
use App\Repository\UserRepository;
use App\Service\AdminStatsService;
use PHPUnit\Framework\TestCase;

final class AdminStatsServiceTest extends TestCase
{
    public function testGetStatsAggregatesRepositoryData(): void
    {
        $transferStats = $this->createStub(TransferStats::class);
        $transferStats->method('getDeletedTransfersCount')->willReturn(5);
        $transferStats->method('getDeletedFilesCount')->willReturn(10);
        $transferStats->method('getDeletedFilesSize')->willReturn(1024);
        $transferStats->method('getDeletedRecipientsCount')->willReturn(3);

        $statsRepo = $this->createStub(TransferStatsRepository::class);
        $statsRepo->method('getSingleton')->willReturn($transferStats);

        $userRepo = $this->createStub(UserRepository::class);
        $userRepo->method('count')->willReturn(100);
        $userRepo->method('countNewThisMonth')->willReturn(8);
        $userRepo->method('countByMonth')->willReturn([]);

        $transferRepo = $this->createStub(TransferRepository::class);
        $transferRepo->method('count')->willReturn(200);
        $transferRepo->method('countActive')->willReturn(50);
        $transferRepo->method('countByMonth')->willReturn([]);

        $fileRepo = $this->createStub(TransferFileRepository::class);
        $fileRepo->method('countAll')->willReturn(500);
        $fileRepo->method('sumSize')->willReturn(2048);

        $recipientRepo = $this->createStub(RecipientRepository::class);
        $recipientRepo->method('countAll')->willReturn(300);
        $recipientRepo->method('countDownloaded')->willReturn(120);

        $stats = new AdminStatsService($userRepo, $transferRepo, $fileRepo, $recipientRepo, $statsRepo);
        $result = $stats->getStats();

        self::assertSame(100, $result['users']['total']);
        self::assertSame(8, $result['users']['newThisMonth']);
        self::assertSame(205, $result['transfers']['total']); // 200 + 5 deleted
        self::assertSame(50, $result['transfers']['active']);
        self::assertSame(510, $result['files']['total']); // 500 + 10 deleted
        self::assertSame(3072, $result['files']['totalSize']); // 2048 + 1024 deleted
        self::assertSame(303, $result['recipients']['total']); // 300 + 3 deleted
        self::assertSame(120, $result['recipients']['downloaded']);
    }
}
