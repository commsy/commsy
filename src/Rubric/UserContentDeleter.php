<?php

namespace App\Rubric;

use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Assessment\AssessmentDeleter;
use App\Entity\Account;
use App\User\UserDeletionHelper;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class UserContentDeleter
{
    /** @param iterable<RubricDeleter> $deleters */
    /** @param iterable<SubEntryRedactor> $subEntryRedactors */
    public function __construct(
        #[AutowireIterator('app.rubric.deleter')]
        private iterable $deleters,
        #[AutowireIterator('app.rubric.sub_entry_redactor')]
        private iterable $subEntryRedactors,
        private AccountSettingsManager $accountSettingsManager,
        private Connection $connection,
        private UserDeletionHelper $userDeletionHelper,
        private AssessmentDeleter $assessmentDeleter,
    ) {}

    /**
     * Erases the footprint of a user in a given context.
     *
     * Depending on the resolved strategy:
     * - CASCADE_ITEMS: Main entries are deleted (via legacy cascade), sub-entries
     *   in surviving parent items are redacted, all references are nullified.
     * - KEEP_ITEMS: Nothing is deleted, only references are nullified.
     */
    public function eraseUserFootprint(int $userId, int $contextId, ?Account $account): void
    {
        $strategy = $this->resolveStrategy($account);

        // 1. Main entries via RubricDeleters. Each deleter is self-contained and
        //    handles its entire cleanup (rubric table, sub-entries, links,
        //    annotations, file links, items row, event dispatch) — identical to
        //    the path taken by the UI delete action.
        foreach ($this->deleters as $deleter) {
            if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
                foreach ($deleter->findItemIdsCreatedBy($userId, $contextId) as $itemId) {
                    $deleter->softDeleteItem($itemId, $userId);
                }
            }

            $deleter->nullifyReferencesInContext($userId, $contextId);
        }

        // 1b. Tasks — not a rubric, handled as an auxiliary table alongside
        //     link_items / annotations. Cascade-delete the ones the user
        //     created (so moderator UIs don't see ghost REQUESTs), then
        //     always nullify any creator references left behind.
        if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
            $this->userDeletionHelper->deleteUserTasks($userId, $contextId, $userId);
        }
        $this->userDeletionHelper->nullifyUserTaskReferences($userId, $contextId);

        // 1c. Assessments — also not a rubric (auxiliary per-user rating on
        //     another item; no UI, no ES, no attachments). Same shape as the
        //     tasks block above: CASCADE cleans the user's own ratings so
        //     they don't linger under a NULL author; KEEP preserves the
        //     numeric value in the rated item's average but erases
        //     authorship.
        if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
            $this->assessmentDeleter->softDeleteAssessmentsByUser($userId, $contextId, $userId);
        }
        $this->assessmentDeleter->nullifyReferencesInContext($userId, $contextId);

        // 2. Sub-entries: never deleted, but redacted (CASCADE) and references nullified (always)
        foreach ($this->subEntryRedactors as $redactor) {
            if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
                $redactor->redactContentOfUser($userId, $contextId);
            }

            $redactor->nullifyReferencesInContext($userId, $contextId);
        }

        // 3. Shared references (items table, files, links)
        $this->cleanupSharedReferences($userId, $contextId);
    }

    /**
     * Cleans up references in tables shared across all rubrics:
     * - files (creator_id)
     * - link_modifier_item (modifier references)
     *
     * Note: the `items` table itself has no `modifier_id` / `creator_id`
     * columns (see initial.sql) — modifier history lives in
     * `link_modifier_item` and is purged below.
     */
    private function cleanupSharedReferences(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE files SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'DELETE FROM link_modifier_item WHERE modifier_id = :userId AND item_id IN (SELECT item_id FROM items WHERE context_id = :contextId)',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    public function resolveStrategy(?Account $account): DeletionStrategy
    {
        if ($account === null) {
            return DeletionStrategy::KEEP_ITEMS;
        }

        $portal = $account->getPortal();
        if ($portal === null) {
            return DeletionStrategy::KEEP_ITEMS;
        }

        // If the portal allows user-defined strategies, check the user's preference
        if ($portal->isAllowUserDefinedDeletionStrategy()) {
            $setting = $this->accountSettingsManager->getSetting(
                $account,
                AccountSetting::USER_DELETION_CASCADING_ITEMS
            );

            return ($setting['enabled'] ?? false)
                ? DeletionStrategy::CASCADE_ITEMS
                : DeletionStrategy::KEEP_ITEMS;
        }

        // Otherwise use the portal-wide default
        return $portal->isCascadingUserDeletionStrategy()
            ? DeletionStrategy::CASCADE_ITEMS
            : DeletionStrategy::KEEP_ITEMS;
    }
}
