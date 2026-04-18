<?php

namespace App\Rubric\Announcement;

use App\Event\ItemDeletedEvent;
use App\Rubric\ItemDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Announcement items without delegating to the legacy
 * `cs_announcement_item::delete()` cascade.
 *
 * Serves as the proof of concept for the Strangler Fig refactoring towards
 * self-contained deletion logic in `src/Rubric/`. Equivalent to the legacy
 * behaviour except that file-link cleanup is applied uniformly (the legacy
 * announcement delete did not clean up `item_link_file` — a latent bug).
 */
class AnnouncementDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Announcement;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM announcement WHERE creator_id = :userId AND context_id = :contextId AND deleter_id IS NULL AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    public function findItemIdsInContext(int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM announcement
                WHERE context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    public function deleteItem(int $itemId, int $deleterId): void
    {
        // 1. Dispatch the deletion event (triggers ES removal via ElasticaSubscriber,
        //    moderator mails via ItemSubscriber, etherpad cleanup, …). The typed item
        //    is loaded lazily — if it no longer exists we silently skip the event.
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Soft-delete the row in the rubric-specific `announcement` table.
        $this->connection->executeStatement(
            'UPDATE announcement
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 3. Soft-delete rows in the `links` table referencing this announcement
        //    (all link types, both directions). Legacy cs_announcement_manager
        //    only hard-deleted `relevant_for` — we unify this across all rubrics
        //    as a soft-delete so restore / audit use cases keep working.
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);

        // 4. Soft-delete `link_items` rows referencing this announcement.
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);

        // 5. Soft-delete any annotations attached to this announcement (plus their
        //    items-twin rows and link_items references).
        $this->itemDeletionHelper->softDeleteAnnotations($itemId, $deleterId);

        // 6. Soft-delete file-link attachments. Announcements can technically carry
        //    file attachments (cs_item::getFileList() is on the base class), but the
        //    legacy delete cascade forgot to clean these up — we do it here for
        //    consistency across all rubrics.
        $this->itemDeletionHelper->softDeleteFileLinks($itemId);

        // 7. Soft-delete the shared `items` table row. Keeping this inside the
        //    deleter makes `deleteItem()` the single source of truth for what it
        //    means to delete an announcement — both the UI delete action and the
        //    user-footprint erasure flow go through the same path.
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE announcement SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE announcement SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    public function hardDeleteOlderThan(int $days): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM announcement
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}
