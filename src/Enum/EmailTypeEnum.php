<?php

declare(strict_types=1);

namespace App\Enum;

enum EmailTypeEnum: string
{
    case TransferReady = 'transfer_ready';
    case TransferDownloaded = 'transfer_downloaded';
    case TransferExpired = 'transfer_expired';
}
