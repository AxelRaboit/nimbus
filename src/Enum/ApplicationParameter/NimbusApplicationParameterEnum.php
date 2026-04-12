<?php

declare(strict_types=1);

namespace App\Enum\ApplicationParameter;

enum NimbusApplicationParameterEnum: string implements ApplicationParameterEnumInterface
{
    case MaxTransferSizeMbPro = 'max_transfer_size_mb_pro';
    case MaxTransferSizeMbFree = 'max_transfer_size_mb_free';
    case MaxFilesPerTransferPro = 'max_files_per_transfer_pro';
    case MaxFilesPerTransferFree = 'max_files_per_transfer_free';
    case MaxExpiryDaysPro = 'max_expiry_days_pro';
    case MaxExpiryHoursFree = 'max_expiry_hours_free';
    case MaxRecipientsPerTransferPro = 'max_recipients_per_transfer_pro';
    case MaxRecipientsPerTransferFree = 'max_recipients_per_transfer_free';
    case RegistrationEnabled = 'registration_enabled';
    case ProTrialDays = 'pro_trial_days';
    case StorageBackend = 'storage_backend';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MaxTransferSizeMbPro => "Taille maximale d'un transfert (Pro, Mo)",
            self::MaxTransferSizeMbFree => "Taille maximale d'un transfert (Free, Mo)",
            self::MaxFilesPerTransferPro => 'Fichiers maximum par transfert (Pro)',
            self::MaxFilesPerTransferFree => 'Fichiers maximum par transfert (Free)',
            self::MaxExpiryDaysPro => 'Expiration maximale (Pro, jours)',
            self::MaxExpiryHoursFree => 'Expiration maximale (Free, heures)',
            self::MaxRecipientsPerTransferPro => 'Destinataires maximum par transfert (Pro)',
            self::MaxRecipientsPerTransferFree => 'Destinataires maximum par transfert (Free)',
            self::RegistrationEnabled => 'Inscription ouverte',
            self::ProTrialDays => 'Durée du trial Pro (jours)',
            self::StorageBackend => 'Backend de stockage',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::MaxTransferSizeMbPro => "Taille maximale d'un transfert en Mo pour le plan Pro",
            self::MaxTransferSizeMbFree => "Taille maximale d'un transfert en Mo pour le plan Free",
            self::MaxFilesPerTransferPro => 'Nombre maximum de fichiers par transfert pour le plan Pro',
            self::MaxFilesPerTransferFree => 'Nombre maximum de fichiers par transfert pour le plan Free',
            self::MaxExpiryDaysPro => "Durée maximale d'expiration en jours pour le plan Pro",
            self::MaxExpiryHoursFree => "Durée maximale d'expiration en heures pour le plan Free",
            self::MaxRecipientsPerTransferPro => 'Nombre maximum de destinataires par transfert (Pro)',
            self::MaxRecipientsPerTransferFree => 'Nombre maximum de destinataires par transfert (Free)',
            self::RegistrationEnabled => 'Inscription ouverte (0 = fermée, 1 = ouverte)',
            self::ProTrialDays => 'Nombre de jours de trial Pro accordés lors du passage en Pro (demo)',
            self::StorageBackend => 'Backend de stockage des fichiers (local ou r2)',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::MaxTransferSizeMbPro => '10000',
            self::MaxTransferSizeMbFree => '100',
            self::MaxFilesPerTransferPro => '20',
            self::MaxFilesPerTransferFree => '3',
            self::MaxExpiryDaysPro => '7',
            self::MaxExpiryHoursFree => '24',
            self::MaxRecipientsPerTransferPro => '20',
            self::MaxRecipientsPerTransferFree => '1',
            self::RegistrationEnabled => '1',
            self::ProTrialDays => '30',
            self::StorageBackend => 'local',
        };
    }

    public function getIntValue(): int
    {
        return (int) $this->getDefaultValue();
    }

    public function getBoolValue(): bool
    {
        return '1' === $this->getDefaultValue();
    }
}
