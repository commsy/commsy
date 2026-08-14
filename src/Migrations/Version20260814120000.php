<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates audit_log, the record of administrative acts inside a portal.
 *
 * Both parties are stored twice on purpose: as a relation and as a copy of
 * the names. The portal key cascades — without the portal the entries have
 * nothing left to say — while the acting account only clears its key, so a
 * deleted account does not take the record of its acts with it.
 */
final class Version20260814120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_log (portal-scoped record of administrative acts).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT NOT NULL,
            portal_id INT NOT NULL,
            occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            event VARCHAR(64) NOT NULL,
            subject_type VARCHAR(32) NOT NULL,
            subject_id INT DEFAULT NULL,
            subject_label VARCHAR(255) NOT NULL,
            subject_name VARCHAR(255) DEFAULT NULL,
            actor_account_id INT DEFAULT NULL,
            actor_username VARCHAR(255) DEFAULT NULL,
            actor_name VARCHAR(255) DEFAULT NULL,
            details JSON DEFAULT NULL COMMENT '(DC2Type:json)',
            INDEX audit_log_portal_occurred_idx (portal_id, occurred_at),
            INDEX audit_log_event_idx (event),
            INDEX IDX_F6E1C0F5A9474AA9 (actor_account_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_audit_log_portal FOREIGN KEY (portal_id) REFERENCES portal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_audit_log_actor FOREIGN KEY (actor_account_id) REFERENCES accounts (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS audit_log');
    }
}
