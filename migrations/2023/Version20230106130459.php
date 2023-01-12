<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230106130459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update groups / grouprooms to always be activated';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE items, labels
            SET items.activation_date = NULL, labels.activation_date = NULL
            WHERE items.item_id = labels.item_id AND labels.type = 'group'
        ");
        $this->addSql("
            UPDATE items, labels
            SET items.modification_date = labels.creation_date, labels.modification_date = labels.creation_date
            WHERE items.item_id = labels.item_id AND labels.type = 'group' AND labels.modification_date > NOW()
        ");

        $this->addSql("
            UPDATE items, room
            SET items.activation_date = NULL
            WHERE items.item_id = room.item_id AND room.type = 'grouproom'
        ");
        $this->addSql("
            UPDATE items, room
            SET items.modification_date = room.creation_date, room.modification_date = room.creation_date
            WHERE items.item_id = room.item_id AND room.type = 'grouproom' AND room.modification_date > NOW()
        ");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
