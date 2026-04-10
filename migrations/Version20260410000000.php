<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and updated_at to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE "user" ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at DROP DEFAULT');
        $this->addSql("COMMENT ON COLUMN \"user\".created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN \"user\".updated_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN created_at');
        $this->addSql('ALTER TABLE "user" DROP COLUMN updated_at');
    }
}
