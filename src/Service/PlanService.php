<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\ApplicationParameter\NimbusApplicationParameterEnum;
use App\Enum\PlanEnum;
use App\Repository\ApplicationParameterRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

readonly class PlanService
{
    public const float PRO_PRICE = 9.99;

    public function __construct(
        private ApplicationParameterRepository $params,
        private EntityManagerInterface $entityManager,
    ) {}

    public function isPro(User $user): bool
    {
        if (PlanEnum::Pro !== $user->getPlan()) {
            return false;
        }

        return !($user->getTrialEndsAt() instanceof DateTimeImmutable && $user->getTrialEndsAt() < new DateTimeImmutable());
    }

    public function isTrialing(User $user): bool
    {
        return $user->getTrialEndsAt() instanceof DateTimeImmutable && $user->getTrialEndsAt() > new DateTimeImmutable();
    }

    public function isFree(User $user): bool
    {
        return PlanEnum::Free === $user->getPlan();
    }

    public function getMaxSizeMb(User $user): int
    {
        return $this->isPro($user) ? $this->getProMaxSizeMb() : $this->getFreeMaxSizeMb();
    }

    public function getMaxFiles(User $user): int
    {
        return $this->isPro($user) ? $this->getProMaxFiles() : $this->getFreeMaxFiles();
    }

    public function getMaxExpiryHours(User $user): int
    {
        return $this->isPro($user) ? $this->getProMaxExpiryDays() * 24 : $this->getFreeMaxExpiryHours();
    }

    public function canAccessMyTransfers(User $user): bool
    {
        return $this->isPro($user);
    }

    public function getProMaxSizeMb(): int
    {
        return (int) $this->params->get('max_transfer_size_mb_pro', '10000');
    }

    public function getProMaxFiles(): int
    {
        return (int) $this->params->get('max_files_per_transfer_pro', '20');
    }

    public function getProMaxExpiryDays(): int
    {
        return (int) $this->params->get('max_expiry_days_pro', '7');
    }

    public function getFreeMaxSizeMb(): int
    {
        return (int) $this->params->get('max_transfer_size_mb_free', '100');
    }

    public function getFreeMaxFiles(): int
    {
        return (int) $this->params->get('max_files_per_transfer_free', '3');
    }

    public function getFreeMaxExpiryHours(): int
    {
        return (int) $this->params->get('max_expiry_hours_free', '24');
    }

    public function getMaxRecipients(User $user): int
    {
        return $this->isPro($user) ? $this->getProMaxRecipients() : $this->getFreeMaxRecipients();
    }

    public function getProMaxRecipients(): int
    {
        return (int) $this->params->get('max_recipients_per_transfer_pro', '20');
    }

    public function getFreeMaxRecipients(): int
    {
        return (int) $this->params->get('max_recipients_per_transfer_free', '1');
    }

    public function getTrialDays(): int
    {
        return (int) $this->params->get(NimbusApplicationParameterEnum::ProTrialDays->value, '30');
    }

    public function upgrade(User $user): void
    {
        $user->setPlan(PlanEnum::Pro);
        $user->setTrialEndsAt(new DateTimeImmutable(sprintf('+%d days midnight', $this->getTrialDays())));

        $this->entityManager->flush();
    }

    public function downgrade(User $user): void
    {
        $user->setPlan(PlanEnum::Free);
        $user->setTrialEndsAt(null);

        $this->entityManager->flush();
    }
}
