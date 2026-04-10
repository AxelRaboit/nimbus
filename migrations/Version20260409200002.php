<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409200002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused pendingPasswordEncrypted columns from transfer and recipient tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer DROP COLUMN IF EXISTS pending_password_encrypted');
        $this->addSql('ALTER TABLE recipient DROP COLUMN IF EXISTS pending_password_encrypted');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer ADD pending_password_encrypted TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE recipient ADD pending_password_encrypted TEXT DEFAULT NULL');
    }
}
