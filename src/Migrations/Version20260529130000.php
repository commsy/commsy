<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the `account_merge_token` table backing the e-mail-token account
 * merge: a single-use, short-lived token (stored as a SHA-256 hash) bound to
 * the old account A (from) and the surviving account N (into).
 *
 * Both account references are FK-bound with ON DELETE CASCADE so that deleting
 * either account — including the merge itself deleting A — removes any pending
 * token automatically, and never blocks account deletion.
 */
final class Version20260529130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create account_merge_token (hashed, single-use e-mail merge token with cascading account FKs).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS account_merge_token (
            id INT AUTO_INCREMENT NOT NULL,
            from_account_id INT NOT NULL,
            into_account_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX account_merge_token_hash_idx (token_hash),
            INDEX IDX_AF769453B0CF99BD (from_account_id),
            INDEX IDX_AF76945317F57087 (into_account_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE account_merge_token ADD CONSTRAINT FK_amt_from_account FOREIGN KEY (from_account_id) REFERENCES accounts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE account_merge_token ADD CONSTRAINT FK_amt_into_account FOREIGN KEY (into_account_id) REFERENCES accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS account_merge_token');
    }
}
