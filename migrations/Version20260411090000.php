<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create transfer_stats table and migrate stats.* from application_parameter';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                CREATE TABLE transfer_stats (
                    id                        INT          NOT NULL DEFAULT 1,
                    deleted_transfers_count   INT          NOT NULL DEFAULT 0,
                    deleted_files_count       INT          NOT NULL DEFAULT 0,
                    deleted_files_size        INT          NOT NULL DEFAULT 0,
                    deleted_recipients_count  INT          NOT NULL DEFAULT 0,
                    PRIMARY KEY (id)
                )
            SQL);

        // Migrate existing counters from application_parameter
        $this->addSql(<<<'SQL'
                INSERT INTO transfer_stats (
                    id,
                    deleted_transfers_count,
                    deleted_files_count,
                    deleted_files_size,
                    deleted_recipients_count
                )
                VALUES (
                    1,
                    COALESCE((SELECT value::int FROM application_parameter WHERE key = 'stats.deleted_transfers_count'), 0),
                    COALESCE((SELECT value::int FROM application_parameter WHERE key = 'stats.deleted_files_count'), 0),
                    COALESCE((SELECT value::int FROM application_parameter WHERE key = 'stats.deleted_files_size'), 0),
                    COALESCE((SELECT value::int FROM application_parameter WHERE key = 'stats.deleted_recipients_count'), 0)
                )
            SQL);

        // Remove stats.* from application_parameter
        $this->addSql("DELETE FROM application_parameter WHERE key LIKE 'stats.%'");
    }

    public function down(Schema $schema): void
    {
        // Restore stats.* into application_parameter before dropping the table
        $this->addSql(<<<'SQL'
                INSERT INTO application_parameter (key, value) SELECT 'stats.deleted_transfers_count',  deleted_transfers_count::text  FROM transfer_stats WHERE id = 1
            SQL);
        $this->addSql(<<<'SQL'
                INSERT INTO application_parameter (key, value) SELECT 'stats.deleted_files_count',      deleted_files_count::text      FROM transfer_stats WHERE id = 1
            SQL);
        $this->addSql(<<<'SQL'
                INSERT INTO application_parameter (key, value) SELECT 'stats.deleted_files_size',       deleted_files_size::text       FROM transfer_stats WHERE id = 1
            SQL);
        $this->addSql(<<<'SQL'
                INSERT INTO application_parameter (key, value) SELECT 'stats.deleted_recipients_count', deleted_recipients_count::text FROM transfer_stats WHERE id = 1
            SQL);

        $this->addSql('DROP TABLE transfer_stats');
    }
}
