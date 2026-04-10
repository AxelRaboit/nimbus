<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes on transfer.status and transfer.expires_at for cleanup queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_transfer_status ON transfer (status)');
        $this->addSql('CREATE INDEX IDX_transfer_expires_at ON transfer (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_transfer_status');
        $this->addSql('DROP INDEX IDX_transfer_expires_at');
    }
}
