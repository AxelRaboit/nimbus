<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412091858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create access_request table for access request feature';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE access_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE access_request (id INT NOT NULL, token VARCHAR(64) NOT NULL, access_token VARCHAR(64) DEFAULT NULL, requester_email VARCHAR(255) NOT NULL, requester_name VARCHAR(255) DEFAULT NULL, message TEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, access_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F3B2558A5F37A13B ON access_request (token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F3B2558AB6A2DD68 ON access_request (access_token)');
        $this->addSql('CREATE INDEX IDX_access_request_token ON access_request (token)');
        $this->addSql('CREATE INDEX IDX_access_request_status ON access_request (status)');
        $this->addSql('ALTER TABLE transfer_file ALTER storage_backend DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE access_request_id_seq CASCADE');
        $this->addSql('DROP TABLE access_request');
        $this->addSql('ALTER TABLE transfer_file ALTER storage_backend SET DEFAULT \'local\'');
    }
}
