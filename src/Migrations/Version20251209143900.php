<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209143900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename user extras key USERROOM to OFFICE using DbConverter::renameExtra';
    }

    public function up(Schema $schema): void
    {
        // Migrate serialized extras key from USERROOM to OFFICE in user table
        DbConverter::renameExtra($this->connection, 'user', 'item_id', 'USERROOM', 'OFFICE');
    }

    public function down(Schema $schema): void
    {
        // Revert extras key back from OFFICE to USERROOM
        DbConverter::renameExtra($this->connection, 'user', 'item_id', 'OFFICE', 'USERROOM');
    }
}
