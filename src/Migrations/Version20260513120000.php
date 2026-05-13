<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * One-shot cleanup of historical drift between the `accounts` table and the
 * legacy `user` table (where every account has one row per portal/room/private
 * room profile).
 *
 * A row in `user` is considered an orphan when it is not soft-deleted but its
 * `account_id` is either NULL or points to an account that no longer exists.
 * The pre-existing AccountDeleter only soft-deleted user rows reachable via
 * the official portal-user membership graph, so any row outside that graph
 * survived deletion and was later re-adopted by the next signup with the
 * same `(username, auth_source)` — the user inheritance bug we are closing.
 *
 * The runtime safeguards live elsewhere (AccountDeleter performs an explicit
 * portal-wide sweep on every account delete; AccountCreatorFacade aborts a
 * signup when matching active rows still exist). This migration only fixes
 * the historical state. After it has run, the runtime safeguards keep the
 * invariant that no non-soft-deleted user row exists without a matching
 * account in the same portal.
 *
 * Soft-delete here is recorded as `deletion_date = NOW(), deleter_id = 0`
 * on both the `user` row AND its twin row in `items` (every user row has a
 * corresponding items row with `type = 'user'`). Audit marker `0`
 * distinguishes these rows from regular user-initiated soft-deletes.
 *
 * Hard-delete is deliberately not done here — that will be Phase 5 once
 * incoming foreign keys to `user.item_id` (creator_id, modifier_id, …) are
 * consistently pruned and the retention window is honored.
 *
 * Steps:
 *  1. Re-link orphan rows to the matching account where unambiguous.
 *  2. Soft-delete the remaining orphans (account gone or never existed).
 *
 * Other cleanup work that one might expect here is intentionally elsewhere
 * — see the comment block at the end of `up()` for the rationale.
 */
final class Version20260513120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'One-shot cleanup of historical (accounts, user) consistency drift.';
    }

    public function up(Schema $schema): void
    {
        // 1. Re-link orphans (account_id IS NULL) to the matching account.
        //    The unique constraint on accounts(portal_id, username, auth_source_id)
        //    guarantees the JOIN matches at most one account per user row, so
        //    ambiguous cases (different portals, same username) cannot
        //    accidentally cross over. Relies on user.portal_id being populated
        //    by Version20251128124048.
        $relinked = (int) $this->connection->executeStatement(<<<'SQL'
            UPDATE user u
            INNER JOIN accounts a
              ON a.username       = u.user_id
             AND a.auth_source_id = u.auth_source
             AND a.portal_id      = u.portal_id
            SET u.account_id = a.id
            WHERE u.account_id    IS NULL
              AND u.deletion_date IS NULL
              AND u.deleter_id    IS NULL
              AND u.portal_id     IS NOT NULL
              AND u.user_id      != 'root'
        SQL);
        $this->write(sprintf('Step 1: re-linked %d user row(s) to their matching account.', $relinked));

        // 2. Soft-delete any user rows that step 1 could not re-link — those
        //    are rows whose original account has been hard-deleted, so the
        //    profile is dangling. Twin row in `items` (one-to-one via item_id)
        //    is soft-deleted in the same statement to keep the two tables in
        //    sync (every user row has a corresponding items row of type
        //    'user', and downstream cleanup queries — e.g. CronHardDelete's
        //    `hardDeleteItemsRows` — read the items row to decide what to
        //    prune).
        $softDeletedOrphans = (int) $this->connection->executeStatement(<<<'SQL'
            UPDATE user u
            INNER JOIN items i ON i.item_id = u.item_id
            SET u.deletion_date = NOW(),
                u.deleter_id    = 0,
                i.deletion_date = NOW(),
                i.deleter_id    = 0
            WHERE u.account_id    IS NULL
              AND u.deletion_date IS NULL
              AND u.deleter_id    IS NULL
              AND u.user_id      != 'root'
        SQL);
        $this->write(sprintf(
            'Step 2: soft-deleted %d orphan user row(s) (audit marker deleter_id=0).',
            $softDeletedOrphans,
        ));

        // Nothing else to do here. Three pieces of cleanup were considered
        // and consciously left out:
        //
        //  - Soft-deleting rows with a broken account_id FK: the FK from
        //    Version20250514125210 makes that state unreachable on a
        //    properly maintained DB; if it ever surfaces (e.g. via
        //    FOREIGN_KEY_CHECKS=0 admin operations), the Phase 4 sanity cron
        //    is the right place to alert, not a one-shot migration.
        //
        //  - Reporting profiles whose firstname/lastname/email diverge from
        //    the linked account: divergence is not a clean identity signal.
        //    `user.use_portal_email = 0` is a designed-in per-room override,
        //    and firstname/lastname drift can simply be propagation lag from
        //    AccountManager::propagateAccountDataToProfiles. Surfacing that
        //    here would produce more noise than insight.
        //
        //  - Nulling dangling creator_id / modifier_id refs: the FKs added
        //    by Version20241001121006 (room) and Version20250514125210
        //    (user self-refs) make dangling refs impossible on the live
        //    schema, and we never hard-delete user rows in this migration
        //    — soft-delete keeps the user.item_id alive so FKs stay valid.
        //    The retention-based hard-delete in Phase 5 will null incoming
        //    refs in the same operation as the actual DELETE.

        $this->write(sprintf(
            'Done. Summary: relinked=%d, softDeletedOrphans=%d.',
            $relinked,
            $softDeletedOrphans,
        ));
    }

    public function down(Schema $schema): void
    {
        // Not reversible — the soft-deletes carry deleter_id=0 as audit marker,
        // but the re-linked account_id values cannot be reliably restored to
        // NULL (some legitimate cases would be mistakenly re-orphaned).
        $this->throwIrreversibleMigrationException(
            'User-consistency cleanup is a one-shot data-fix migration; rollback is not supported.'
        );
    }
}
