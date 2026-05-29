<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds single-use, short-lived account-merge token columns to the `hash` table.
 *
 * These back the e-mail-token legitimation flow that lets a user merge an
 * externally authenticated "old" account (no local password to verify) into
 * their current account: the token is mailed to the old account's address and
 * confirms the merge on click.
 */
final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add merge-token columns (token, from/into account id, expiry) + index to hash table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE hash ADD COLUMN IF NOT EXISTS merge_token VARCHAR(64) DEFAULT NULL");
        $this->addSql("ALTER TABLE hash ADD COLUMN IF NOT EXISTS merge_from_account_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE hash ADD COLUMN IF NOT EXISTS merge_into_account_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE hash ADD COLUMN IF NOT EXISTS merge_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE INDEX IF NOT EXISTS merge_token ON hash (merge_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS merge_token ON hash');
        $this->addSql('ALTER TABLE hash DROP COLUMN IF EXISTS merge_token');
        $this->addSql('ALTER TABLE hash DROP COLUMN IF EXISTS merge_from_account_id');
        $this->addSql('ALTER TABLE hash DROP COLUMN IF EXISTS merge_into_account_id');
        $this->addSql('ALTER TABLE hash DROP COLUMN IF EXISTS merge_expires_at');
    }
}
