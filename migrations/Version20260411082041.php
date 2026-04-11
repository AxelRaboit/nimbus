<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411082041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_transfer_user_id RENAME TO IDX_4034A3C0A76ED395');
        $this->addSql('ALTER TABLE transfer_stats ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ADD locale VARCHAR(5) NOT NULL DEFAULT \'fr\'');
        $this->addSql('ALTER TABLE "user" ALTER plan DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER pro_until TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_4034a3c0a76ed395 RENAME TO idx_transfer_user_id');
        $this->addSql('ALTER TABLE transfer_stats ALTER id SET DEFAULT 1');
        $this->addSql('ALTER TABLE "user" DROP locale');
        $this->addSql('ALTER TABLE "user" ALTER plan SET DEFAULT \'free\'');
        $this->addSql('ALTER TABLE "user" ALTER pro_until TYPE TIMESTAMP(0) WITH TIME ZONE');
    }
}
