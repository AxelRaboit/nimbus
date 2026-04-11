<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename pro_until to trial_ends_at on user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN pro_until TO trial_ends_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN trial_ends_at TO pro_until');
    }
}
