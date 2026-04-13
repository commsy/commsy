<?php

namespace App\Rubric;

use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
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

        // 1. Main entries via RubricDeleters (rubric-specific tables only)
        foreach ($this->deleters as $deleter) {
            if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
                foreach ($deleter->findItemsCreatedBy($userId, $contextId) as $item) {
                    $deleter->deleteItem($item);
                }
            }

            $deleter->nullifyReferencesInContext($userId, $contextId);
        }

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
     * - items table (modifier_id)
     * - files (creator_id)
     * - link_modifier_item (modifier references)
     */
    private function cleanupSharedReferences(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE items SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

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
