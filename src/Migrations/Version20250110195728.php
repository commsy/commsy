<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250110195728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ROOM_SETTINGS_SLUG_HELP translation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translation (context_id, translation_key, translation_de, translation_en) SELECT portal.id, \'ROOM_SETTINGS_SLUG_HELP\', \'\', \'\' FROM portal;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translation WHERE translation_key = \'ROOM_SETTINGS_SLUG_HELP\'');
    }
}
