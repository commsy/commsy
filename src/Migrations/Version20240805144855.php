<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240805144855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop deprecated extras';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'room_privat', 'item_id', [
            'MOVE'
        ]);
        DbConverter::removeExtra($this->connection, 'room', 'item_id', [
            'MOVE'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
