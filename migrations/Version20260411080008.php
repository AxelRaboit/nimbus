<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411080008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename application_parameter keys to _pro/_free suffixes and seed Free limits';
    }

    public function up(Schema $schema): void
    {
        // Rename existing Pro keys
        $this->addSql("UPDATE application_parameter SET key = 'max_transfer_size_mb_pro' WHERE key = 'max_transfer_size_mb'");
        $this->addSql("UPDATE application_parameter SET key = 'max_files_per_transfer_pro' WHERE key = 'max_files_per_transfer'");
        $this->addSql("UPDATE application_parameter SET key = 'max_expiry_days_pro' WHERE key = 'max_expiry_days'");
        $this->addSql("UPDATE application_parameter SET key = 'max_recipients_per_transfer_pro' WHERE key = 'max_recipients_per_transfer'");

        // Insert Free limits
        $this->addSql("INSERT INTO application_parameter (key, value, description) VALUES ('max_transfer_size_mb_free', '100', 'Taille maximale d''un transfert en Mo pour le plan Free') ON CONFLICT (key) DO NOTHING");
        $this->addSql("INSERT INTO application_parameter (key, value, description) VALUES ('max_files_per_transfer_free', '3', 'Nombre maximum de fichiers par transfert pour le plan Free') ON CONFLICT (key) DO NOTHING");
        $this->addSql("INSERT INTO application_parameter (key, value, description) VALUES ('max_expiry_hours_free', '24', 'Durée maximale d''expiration en heures pour le plan Free') ON CONFLICT (key) DO NOTHING");
        $this->addSql("INSERT INTO application_parameter (key, value, description) VALUES ('max_recipients_per_transfer_free', '1', 'Nombre maximum de destinataires par transfert pour le plan Free') ON CONFLICT (key) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        // Restore original Pro key names
        $this->addSql("UPDATE application_parameter SET key = 'max_transfer_size_mb' WHERE key = 'max_transfer_size_mb_pro'");
        $this->addSql("UPDATE application_parameter SET key = 'max_files_per_transfer' WHERE key = 'max_files_per_transfer_pro'");
        $this->addSql("UPDATE application_parameter SET key = 'max_expiry_days' WHERE key = 'max_expiry_days_pro'");
        $this->addSql("UPDATE application_parameter SET key = 'max_recipients_per_transfer' WHERE key = 'max_recipients_per_transfer_pro'");

        // Remove Free limits
        $this->addSql("DELETE FROM application_parameter WHERE key IN ('max_transfer_size_mb_free', 'max_files_per_transfer_free', 'max_expiry_hours_free', 'max_recipients_per_transfer_free')");
    }
}
