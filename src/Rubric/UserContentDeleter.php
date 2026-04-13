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
    public function __construct(
        #[AutowireIterator('app.rubric.deleter')]
        private iterable $deleters,
        private AccountSettingsManager $accountSettingsManager,
        private Connection $connection,
    ) {}

    /**
     * Erases the footprint of a user in a given context.
     * Depending on the resolved strategy, items created by the user are either
     * deleted (CASCADE_ITEMS) or kept with nullified references (KEEP_ITEMS).
     *
     * References (creator_id, modifier_id) are always nullified.
     */
    public function eraseUserFootprint(int $userId, int $contextId, ?Account $account): void
    {
        $strategy = $this->resolveStrategy($account);

        foreach ($this->deleters as $deleter) {
            if ($strategy === DeletionStrategy::CASCADE_ITEMS) {
                foreach ($deleter->findItemsCreatedBy($userId, $contextId) as $item) {
                    $deleter->deleteItem($item);
                }
            }

            $deleter->nullifyReferencesInContext($userId, $contextId);
        }

        $this->nullifyCrossRubricReferences($userId, $contextId);
    }

    /**
     * Nullifies references in tables that are not rubric-specific:
     * - files.creator_id
     * - link_modifier_item (rows deleted, since modifier_id is part of PK)
     * - items.deleter_id (for items in this context)
     */
    private function nullifyCrossRubricReferences(int $userId, int $contextId): void
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
