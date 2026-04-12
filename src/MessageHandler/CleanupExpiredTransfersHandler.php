<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\TransferStatusEnum;
use App\Manager\TransferManager;
use App\Message\CleanupExpiredTransfersMessage;
use App\Repository\TransferRepository;
use App\Service\PlanService;
use App\Service\TusUploadServiceInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CleanupExpiredTransfersHandler
{
    public function __construct(
        private TransferRepository $transferRepository,
        private TransferManager $transferManager,
        private TusUploadServiceInterface $tusUploadService,
        private PlanService $planService,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(CleanupExpiredTransfersMessage $message): void
    {
        $expired = $this->transferRepository->createQueryBuilder('t')
            ->where('t.expiresAt < :now')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('statuses', [TransferStatusEnum::Pending, TransferStatusEnum::Ready])
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($expired as $transfer) {
            $transfer->setStatus(TransferStatusEnum::Expired);
            $this->transferManager->delete($transfer);
            ++$count;
        }

        if ($count > 0) {
            $this->logger->info('Cleaned up {count} expired transfer(s).', ['count' => $count]);
        }

        $maxAgeSeconds = $this->planService->getTusCleanupMaxAgeHours() * 3600;
        $orphaned = $this->tusUploadService->cleanupOrphanedUploads($maxAgeSeconds);
        if ($orphaned > 0) {
            $this->logger->info('Cleaned up {count} orphaned TUS upload(s).', ['count' => $orphaned]);
        }
    }
}
