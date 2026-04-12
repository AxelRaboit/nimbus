<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add storage_backend column to transfer_file (local | r2)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE transfer_file ADD COLUMN storage_backend VARCHAR(20) NOT NULL DEFAULT 'local'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_file DROP COLUMN storage_backend');
    }
}
