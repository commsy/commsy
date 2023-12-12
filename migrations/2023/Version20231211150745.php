<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231211150745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop deprecated extras';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'room_privat', 'item_id', [
            'CS_BAR_SHOW_CONNECTION'
        ]);

        DbConverter::removeExtra($this->connection, 'user', 'item_id', [
            'CONNECTION_ARRAY'
        ]);

        DbConverter::removeExtra($this->connection, 'server', 'item_id', [
            'CONNECTION_OWNKEY', 'SCRIBD_SECRET', 'SCRIBD_API_KEY', 'VERSION', 'DEFAULT_AUTH', 'DEFAULT_PORTAL_ID',
            'DEFAULT_SENDER_ADDRESS', 'OUTOFSERVICE', 'OUTOFSERVICE_SHOW', 'CONNECTION_ARRAY', 'USAGE_INFO',
            'USAGE_INFO_FORM', 'USAGE_INFO_HEADER', 'USAGE_INFO_FORM_HEADER', 'USAGE_INFO_TEXT', 'USAGE_INFO_FORM_TEXT'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
