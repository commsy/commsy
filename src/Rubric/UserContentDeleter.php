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
     * - CASCADE_ITEMS: main entries deleted, sub-entries in surviving parent
     *   items redacted, references nullified.
     * - KEEP_ITEMS: nothing deleted, only references nullified.
     */
    public function eraseUserFootprint(int $userId, int $contextId, ?Account $account): void
    {
        $strategy = $this->resolveStrategy($account);

        // 1. Main entries via RubricDeleters.
        foreach ($this->deleters as $deleter) {
            if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
                foreach ($deleter->findItemIdsCreatedBy($userId, $contextId) as $itemId) {
                    $deleter->softDeleteItem($itemId, $userId);
                }
            }

            $deleter->nullifyReferencesInContext($userId, $contextId);
        }

        // 1b. Tasks — auxiliary, not a rubric.
        if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
            $this->userDeletionHelper->deleteUserTasks($userId, $contextId, $userId);
        }
        $this->userDeletionHelper->nullifyUserTaskReferences($userId, $contextId);

        // 1c. Assessments — auxiliary per-user ratings. KEEP preserves the
        //     numeric value in the rated item's average but erases authorship.
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
     * `files.creator_id` and `link_modifier_item`. The `items` table has no
     * `modifier_id` / `creator_id` — modifier history lives in
     * `link_modifier_item`.
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

        if ($portal->isAllowUserDefinedDeletionStrategy()) {
            $setting = $this->accountSettingsManager->getSetting(
                $account,
                AccountSetting::USER_DELETION_CASCADING_ITEMS
            );

            return ($setting['enabled'] ?? false)
                ? DeletionStrategy::CASCADE_ITEMS
                : DeletionStrategy::KEEP_ITEMS;
        }

        return $portal->isCascadingUserDeletionStrategy()
            ? DeletionStrategy::CASCADE_ITEMS
            : DeletionStrategy::KEEP_ITEMS;
    }
}
