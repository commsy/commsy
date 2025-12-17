<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate all records from room_privat into room (type = 'privateroom') and drop room_privat.
 */
final class Version20251208150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move room_privat rows into room (type=privateroom); drop room_privat table.';
    }

    public function up(Schema $schema): void
    {
        // Copy data from room_privat to room, keeping original IDs; skip if ID already exists in room
        $this->addSql("INSERT INTO room (
            item_id, context_id, title, extras, status, activity, type,
            is_open_for_guests, continuous, template, contact_persons, lastlogin,
            creation_date, modification_date, deleter_id, deletion_date, creator_id, modifier_id
        )
        SELECT rp.item_id, rp.context_id, rp.title, rp.extras, rp.status, rp.activity, 'privateroom',
               rp.is_open_for_guests, rp.continuous, rp.template, rp.contact_persons, rp.lastlogin,
               rp.creation_date, rp.modification_date, rp.deleter_id, rp.deletion_date, rp.creator_id, rp.modifier_id
        FROM room_privat rp
        LEFT JOIN room r ON r.item_id = rp.item_id
        WHERE r.item_id IS NULL");

        // Drop the legacy table after migration
        $this->addSql('DROP TABLE IF EXISTS room_privat');
    }

    public function down(Schema $schema): void
    {
        // Recreate a minimal room_privat table to allow rollback; data will not be recovered
        $this->addSql("CREATE TABLE room_privat (
            item_id INT NOT NULL,
            context_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            extras LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            activity INT DEFAULT 0 NOT NULL,
            type VARCHAR(20) NOT NULL,
            public TINYINT(1) DEFAULT 0 NOT NULL,
            is_open_for_guests TINYINT(1) DEFAULT 0 NOT NULL,
            continuous TINYINT(1) DEFAULT 0 NOT NULL,
            template TINYINT(1) DEFAULT 0 NOT NULL,
            contact_persons VARCHAR(255) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            lastlogin DATETIME DEFAULT NULL,
            creation_date DATETIME NOT NULL,
            modification_date DATETIME NOT NULL,
            deleter_id INT DEFAULT NULL,
            deletion_date DATETIME DEFAULT NULL,
            creator_id INT DEFAULT NULL,
            modifier_id INT DEFAULT NULL,
            PRIMARY KEY(item_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }
}
