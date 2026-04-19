<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419125832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename all tables to plural form';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_request RENAME TO access_requests');
        $this->addSql('ALTER TABLE application_parameter RENAME TO application_parameters');
        $this->addSql('ALTER TABLE recipient RENAME TO recipients');
        $this->addSql('ALTER TABLE reset_password_request RENAME TO reset_password_requests');
        $this->addSql('ALTER TABLE transfer RENAME TO transfers');
        $this->addSql('ALTER TABLE transfer_file RENAME TO transfer_files');
        $this->addSql('ALTER TABLE "user" RENAME TO users');

        $this->addSql('ALTER SEQUENCE access_request_id_seq RENAME TO access_requests_id_seq');
        $this->addSql('ALTER SEQUENCE recipient_id_seq RENAME TO recipients_id_seq');
        $this->addSql('ALTER SEQUENCE transfer_id_seq RENAME TO transfers_id_seq');
        $this->addSql('ALTER SEQUENCE transfer_file_id_seq RENAME TO transfer_files_id_seq');
        $this->addSql('ALTER SEQUENCE user_id_seq RENAME TO users_id_seq');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_requests RENAME TO access_request');
        $this->addSql('ALTER TABLE application_parameters RENAME TO application_parameter');
        $this->addSql('ALTER TABLE recipients RENAME TO recipient');
        $this->addSql('ALTER TABLE reset_password_requests RENAME TO reset_password_request');
        $this->addSql('ALTER TABLE transfers RENAME TO transfer');
        $this->addSql('ALTER TABLE transfer_files RENAME TO transfer_file');
        $this->addSql('ALTER TABLE users RENAME TO "user"');

        $this->addSql('ALTER SEQUENCE access_requests_id_seq RENAME TO access_request_id_seq');
        $this->addSql('ALTER SEQUENCE recipients_id_seq RENAME TO recipient_id_seq');
        $this->addSql('ALTER SEQUENCE transfers_id_seq RENAME TO transfer_id_seq');
        $this->addSql('ALTER SEQUENCE transfer_files_id_seq RENAME TO transfer_file_id_seq');
        $this->addSql('ALTER SEQUENCE users_id_seq RENAME TO user_id_seq');
    }
}
