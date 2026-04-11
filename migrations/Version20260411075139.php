<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411075139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add plan column to user table (free/pro)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD plan VARCHAR(10) NOT NULL DEFAULT \'free\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN plan');
    }
}
