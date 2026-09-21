<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the `notification` table backing the activity-notifications feature.
 *
 * One row per recipient per event (fan-out); each create and each edit of a
 * feed-relevant entry is its own row, so there is deliberately no per-item
 * uniqueness. `action` distinguishes create from edit, `payload` (JSON) holds
 * the rubric-specific display extras, while the queryable columns stay typed and
 * indexed. Display data is snapshotted so the render path needs no legacy
 * lookups. `recipient_id` is FK-bound to `accounts` with ON DELETE CASCADE so
 * deleting an account removes its notifications.
 */
final class Version20260611120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table (per-recipient activity rows, action + JSON payload, cascading account FK).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS notification (
            id INT AUTO_INCREMENT NOT NULL,
            recipient_id INT NOT NULL,
            type VARCHAR(32) NOT NULL,
            action VARCHAR(16) DEFAULT NULL,
            context_id INT NOT NULL,
            source_item_id INT DEFAULT NULL,
            source_item_type VARCHAR(32) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            room_title VARCHAR(255) NOT NULL,
            actor_name VARCHAR(255) DEFAULT NULL,
            payload JSON DEFAULT NULL COMMENT '(DC2Type:json)',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX notification_recipient_idx (recipient_id, read_at, created_at),
            INDEX notification_source_item_idx (source_item_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_notification_recipient FOREIGN KEY (recipient_id) REFERENCES accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notification');
    }
}
