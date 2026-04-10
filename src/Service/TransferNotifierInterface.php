<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recipient;
use App\Entity\Transfer;

interface TransferNotifierInterface
{
    public function notifyReady(Transfer $transfer, ?string $plainPassword = null): void;

    public function notifyDownloaded(Transfer $transfer, Recipient $recipient): void;

    public function notifyExpired(Transfer $transfer): void;

    public function notifyReminder(Transfer $transfer, Recipient $recipient): void;
}
