<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240926133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop deprecated extras';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'user', 'item_id', [
            'CONNECTION_EXTERNAL_KEY_ARRAY',
            'CONNECTION_OWNKEY',
            'CONNECTION_ARRAY',
            'ADMINCOMMENT',
            'PUBLISHWANTMAIL',
            'DEFAULT_MAIL_VISIBILITY',
            'PW_GENERATION_',
            'PASSWORD_EXPIRED_EMAIL'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
