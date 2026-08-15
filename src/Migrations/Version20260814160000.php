<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates room_export, the record of a room's export runs.
 *
 * The room key cascades — export rows of a room that is gone have nothing left
 * to say, and the database enforces that whichever code path removed the room.
 * The requesting person is kept as a plain id with a copy of the name instead of
 * a key: a room membership disappears when someone leaves, and the record of who
 * asked for an archive should outlive that.
 */
final class Version20260814160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create room_export (record of a room\'s export runs).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS room_export (
            id INT AUTO_INCREMENT NOT NULL,
            room_id INT NOT NULL,
            status VARCHAR(16) NOT NULL,
            requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            finished_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            requested_by_user_id INT DEFAULT NULL,
            requested_by_name VARCHAR(255) DEFAULT NULL,
            filename VARCHAR(255) DEFAULT NULL,
            size_in_bytes INT DEFAULT NULL,
            failure_reason VARCHAR(255) DEFAULT NULL,
            INDEX room_export_room_status_idx (room_id, status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE room_export ADD CONSTRAINT FK_room_export_room FOREIGN KEY (room_id) REFERENCES room (item_id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS room_export');
    }
}
