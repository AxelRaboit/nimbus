<?php

declare(strict_types=1);

namespace App\Enum\ApplicationParameter;

enum NimbusApplicationParameterEnum: string implements ApplicationParameterEnumInterface
{
    case MaxTransferSizeMb = 'max_transfer_size_mb';
    case MaxFilesPerTransfer = 'max_files_per_transfer';
    case MaxRecipientsPerTransfer = 'max_recipients_per_transfer';
    case MaxExpiryDays = 'max_expiry_days';
    case MaintenanceMode = 'maintenance_mode';
    case RegistrationEnabled = 'registration_enabled';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MaxTransferSizeMb => "Taille maximale d'un transfert",
            self::MaxFilesPerTransfer => 'Fichiers maximum par transfert',
            self::MaxRecipientsPerTransfer => 'Destinataires maximum par transfert',
            self::MaxExpiryDays => 'Expiration maximale (jours)',
            self::MaintenanceMode => 'Mode maintenance',
            self::RegistrationEnabled => 'Inscription ouverte',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::MaxTransferSizeMb => "Taille maximale d'un transfert en Mo",
            self::MaxFilesPerTransfer => 'Nombre maximum de fichiers par transfert',
            self::MaxRecipientsPerTransfer => 'Nombre maximum de destinataires par transfert',
            self::MaxExpiryDays => 'Durée maximale d\'expiration en jours',
            self::MaintenanceMode => 'Mode maintenance (0 = désactivé, 1 = activé)',
            self::RegistrationEnabled => 'Inscription ouverte (0 = fermée, 1 = ouverte)',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::MaxTransferSizeMb => '500',
            self::MaxFilesPerTransfer => '20',
            self::MaxRecipientsPerTransfer => '20',
            self::MaxExpiryDays => '7',
            self::MaintenanceMode => '0',
            self::RegistrationEnabled => '1',
        };
    }

    public function getIntValue(): int
    {
        return (int) $this->getDefaultValue();
    }

    public function getBoolValue(): bool
    {
        return (bool) $this->getDefaultValue();
    }
}
