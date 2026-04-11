<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TransferStatsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferStatsRepository::class)]
#[ORM\Table(name: 'transfer_stats')]
class TransferStats
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => 0])]
    private int $deletedTransfersCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $deletedFilesCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $deletedFilesSize = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $deletedRecipientsCount = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDeletedTransfersCount(): int
    {
        return $this->deletedTransfersCount;
    }

    public function getDeletedFilesCount(): int
    {
        return $this->deletedFilesCount;
    }

    public function getDeletedFilesSize(): int
    {
        return $this->deletedFilesSize;
    }

    public function getDeletedRecipientsCount(): int
    {
        return $this->deletedRecipientsCount;
    }
}
