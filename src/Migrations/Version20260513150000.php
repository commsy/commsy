<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Finalises the move of user identity from the `(user_id, auth_source)`
 * tuple to the `account_id` FK.
 *
 *   - drops the now-unused `user.auth_source` column,
 *   - swaps `unique_non_soft_deleted_idx` from
 *     `(user_id, auth_source, context_id, not_deleted)` to
 *     `(account_id, context_id, not_deleted)`,
 *   - changes the FK `user.account_id` → `accounts.id` to
 *     `ON DELETE SET NULL` so removing an account row automatically
 *     detaches every surviving (soft-deleted) user reference.
 *
 * The companion invariant "a non-soft-deleted user row must carry an
 * `account_id`" is enforced at the application layer
 * ({@see \App\Facade\AccountCreatorFacade::persistNewAccount} guard,
 * {@see \App\Account\AccountDeleter::delete} sweep, the Phase 2 cleanup
 * migration) rather than via a CHECK constraint — MariaDB refuses
 * `CHECK (account_id IS NOT NULL OR deletion_date IS NOT NULL)` on this
 * schema with "Function or expression 'account_id' cannot be used in
 * the CHECK clause" because of the existing generated `not_deleted`
 * column. The application enforcement is sufficient: the Phase 4
 * sanity cron will alert on drift.
 *
 * Pre-requisites:
 *  - `Version20260513120000` cleaned every orphan that lacked an
 *     account link.
 *  - The Phase 3a/3b code migration switched every identity-bound lookup
 *     to `account_id`, so removing `auth_source` cannot break any query.
 */
final class Version20260513150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop user.auth_source; rekey unique index on account_id; FK user.account_id ON DELETE SET NULL.';
    }

    public function up(Schema $schema): void
    {
        // 1. Swap the unique non-soft-deleted index off (user_id, auth_source)
        //    onto (account_id) — must precede the column drop because the
        //    old index still references `auth_source`.
        $this->addSql('DROP INDEX IF EXISTS unique_non_soft_deleted_idx ON user');
        $this->addSql('CREATE UNIQUE INDEX unique_non_soft_deleted_idx ON user (account_id, context_id, not_deleted)');

        // 2. Drop the `auth_source` column.
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS auth_source');

        // 3. Recreate the account_id FK with ON DELETE SET NULL — removing
        //    an account row then automatically detaches every surviving
        //    (soft-deleted) user reference.
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY IF EXISTS FK_8D93D6499B6B5FBA');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Restore the FK to its previous behaviour (no ON DELETE clause).
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499B6B5FBA');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');

        // Recreate the `auth_source` column and populate it from the linked
        // account. Rows whose `account_id` is NULL (soft-deleted with no
        // surviving account) cannot have their original auth_source
        // recovered — those keep `auth_source = NULL`.
        $this->addSql('ALTER TABLE user ADD auth_source INT DEFAULT NULL');
        $this->addSql('UPDATE user u INNER JOIN accounts a ON a.id = u.account_id SET u.auth_source = a.auth_source_id');

        // Swap the unique index back to the legacy column set.
        $this->addSql('DROP INDEX unique_non_soft_deleted_idx ON user');
        $this->addSql('CREATE UNIQUE INDEX unique_non_soft_deleted_idx ON user (user_id, auth_source, context_id, not_deleted)');
    }
}
