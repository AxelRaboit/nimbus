<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409221542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create application_parameter table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                CREATE TABLE application_parameter (
                    key VARCHAR(100) NOT NULL,
                    value TEXT DEFAULT NULL,
                    description VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY(key)
                )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE application_parameter');
    }
}
