<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Rubric\Annotation;

use App\Event\ItemDeletedEvent;
use App\Rubric\ItemDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes a single Annotation item without delegating to the legacy
 * `cs_annotation_item::delete()` / `cs_annotations_manager::delete()`
 * cascade.
 *
 * Note the distinction from {@see ItemDeletionHelper::softDeleteAnnotations()}:
 * that helper is keyed by the *parent* item (soft-deletes every annotation
 * whose `linked_item_id` matches), and is called from the parent rubric's
 * deleter while the parent is being removed. This class operates per
 * annotation id — it is what the UI uses when the user explicitly deletes
 * their own comment on an otherwise-alive parent item, and what
 * {@see \App\Action\Delete\DeleteGeneric} dispatches to when it encounters
 * a free-standing annotation.
 *
 * Legacy parity: `cs_annotations_manager::delete()` soft-deletes the
 * `annotations` row and calls `parent::delete()` to soft-delete the
 * `items` twin; nothing else. Annotations technically extend `cs_item`,
 * so they can carry link_items / links / file attachments in theory —
 * we clean those up for consistency with every other rubric deleter.
 */
class AnnotationDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Annotation;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM annotations
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    public function deleteItem(int $itemId, int $deleterId): void
    {
        // 1. Dispatch the deletion event (ES cleanup, mail notifications, …).
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Soft-delete the `annotations` row.
        $this->connection->executeStatement(
            'UPDATE annotations
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 3. Soft-delete any `links` row referencing this annotation. Legacy
        //    cs_annotations_manager did not clean `links` up; we do it here
        //    for uniform behaviour across all rubrics.
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);

        // 4. Soft-delete `link_items` rows referencing this annotation (plus
        //    their `items` twin rows, see ItemDeletionHelper).
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);

        // 5. Soft-delete file-link attachments. Annotations can technically
        //    carry files via the base class; legacy cascade did not clean
        //    these up — we do, matching every other rubric deleter.
        $this->itemDeletionHelper->softDeleteFileLinks($itemId);

        // 6. Soft-delete the shared `items` table row.
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE annotations SET creator_id = NULL
                WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE annotations SET modifier_id = NULL
                WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
