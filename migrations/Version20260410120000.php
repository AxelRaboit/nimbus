<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add public link support to transfers (is_public flag + public download counter)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer ADD is_public BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE transfer ADD public_download_count INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer DROP is_public');
        $this->addSql('ALTER TABLE transfer DROP public_download_count');
    }
}
