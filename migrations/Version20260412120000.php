<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-user custom file size limit and requested/granted file size on access requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_request ADD requested_file_size_mb INT DEFAULT NULL');
        $this->addSql('ALTER TABLE access_request ADD granted_file_size_mb INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD custom_file_size_mb INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_request DROP requested_file_size_mb');
        $this->addSql('ALTER TABLE access_request DROP granted_file_size_mb');
        $this->addSql('ALTER TABLE "user" DROP custom_file_size_mb');
    }
}
