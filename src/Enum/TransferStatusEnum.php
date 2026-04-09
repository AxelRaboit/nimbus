<?php

declare(strict_types=1);

namespace App\Enum;

enum TransferStatusEnum: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Expired = 'expired';
    case Deleted = 'deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'enum.transfer_status.pending',
            self::Ready => 'enum.transfer_status.ready',
            self::Expired => 'enum.transfer_status.expired',
            self::Deleted => 'enum.transfer_status.deleted',
        };
    }
}
