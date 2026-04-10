<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id FK to transfer table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer ADD CONSTRAINT FK_transfer_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_transfer_user_id ON transfer (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer DROP CONSTRAINT FK_transfer_user');
        $this->addSql('DROP INDEX IDX_transfer_user_id');
        $this->addSql('ALTER TABLE transfer DROP COLUMN user_id');
    }
}
