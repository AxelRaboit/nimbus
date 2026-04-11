<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\RecipientRepository;
use App\Repository\TransferFileRepository;
use App\Repository\TransferRepository;
use App\Repository\TransferStatsRepository;
use App\Repository\UserRepository;

final readonly class AdminStatsService
{
    public function __construct(
        private UserRepository $userRepository,
        private TransferRepository $transferRepository,
        private TransferFileRepository $transferFileRepository,
        private RecipientRepository $recipientRepository,
        private TransferStatsRepository $transferStatsRepository,
    ) {}

    public function getStats(): array
    {
        $historical = $this->transferStatsRepository->getSingleton();

        return [
            'users' => [
                'total' => $this->userRepository->count([]),
                'newThisMonth' => $this->userRepository->countNewThisMonth(),
            ],
            'transfers' => [
                'total' => $this->transferRepository->count([]) + $historical->getDeletedTransfersCount(),
                'active' => $this->transferRepository->countActive(),
                'byStatus' => $this->transferRepository->count([]),
            ],
            'files' => [
                'total' => $this->transferFileRepository->countAll() + $historical->getDeletedFilesCount(),
                'totalSize' => $this->transferFileRepository->sumSize() + $historical->getDeletedFilesSize(),
            ],
            'recipients' => [
                'total' => $this->recipientRepository->countAll() + $historical->getDeletedRecipientsCount(),
                'downloaded' => $this->recipientRepository->countDownloaded(),
            ],
            'usersByMonth' => $this->userRepository->countByMonth(6),
            'transfersByMonth' => $this->transferRepository->countByMonth(6),
        ];
    }
}
