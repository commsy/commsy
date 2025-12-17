<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230112151330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unnecessary extras';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'labels', 'item_id', [
            'GROUP_ROOM_ACTIVE',
            'DISCUSSION_NOTIFICATION_ARRAY',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
