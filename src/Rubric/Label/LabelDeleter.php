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

namespace App\Rubric\Label;

use App\Event\ItemDeletedEvent;
use App\Rubric\ItemDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes items of the complete `cs_label_item` hierarchy (topic / hashtag /
 * buzzword / timepulse / institution / group) without delegating to the legacy
 * `cs_label_item::delete()` / `cs_labels_manager::delete()` cascade.
 *
 * Legacy structure that this mirrors:
 * - `cs_label_item` holds the shared state; every subtype (`cs_topic_item`,
 *   `cs_buzzword_item`, `cs_group_item`, …) inherits it and only differs in
 *   the `labels.type` column, the `items.type` column is always `'label'`.
 * - `cs_group_manager extends cs_labels_manager` (etc.) — none of the
 *   subtype-specific managers override `delete()`, so a single deleter
 *   covers the whole tree, matching the legacy inheritance chain.
 *
 * The one subtype-specific Legacy extension — `cs_group_item::delete($deleteGrouproom=true)`
 * triggering a cascade into the linked grouproom — is intentionally **not**
 * reproduced here. All current call sites handle the grouproom either out-of-band
 * (drafts have no grouproom yet, because `GroupController::create` calls
 * `save(false)`) or explicitly before reaching this deleter
 * (`ProfileController::deleteRoomProfile` deletes the grouproom separately
 * on the preceding line). Grouproom deletion will move to the room-level
 * deleter infrastructure introduced later in #5082.
 *
 * Legacy parity otherwise:
 * - Soft-deletes the `labels` row.
 * - Soft-deletes `links` rows referencing the label (legacy did this via
 *   `cs_link_manager::deleteLinksBecauseItemIsDeleted`).
 * - Soft-deletes the `items` twin row.
 *
 * Behaviour added on top of Legacy (fixes for uniform cleanup — cf. the same
 * pattern in every other `RubricDeleter`):
 * - Soft-deletes `link_items` rows. Legacy labels_manager did not — groups
 *   use `link_items` for membership, so leaving those rows dangling against
 *   a deleted group was a latent inconsistency.
 * - Soft-deletes `item_link_file` attachments. Labels rarely carry files,
 *   but `cs_label_item extends cs_item` so it is technically possible.
 * - Dispatches `ItemDeletedEvent` so `ElasticaSubscriber::onItemDeleted`
 *   removes the document from the `commsy_label` index. Legacy did this
 *   via `cs_label_item::deleteElasticItem(...)`.
 */
class LabelDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Label;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM labels
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
        // 1. Dispatch the deletion event (triggers ES removal from the
        //    `commsy_label` index via ElasticaSubscriber). The typed item is
        //    loaded lazily — if it no longer exists we silently skip.
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Soft-delete the row in the `labels` table (covers every subtype
        //    — the subtype is stored in `labels.type`, not in a separate table).
        $this->connection->executeStatement(
            'UPDATE labels
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 3. Soft-delete `links` rows referencing this label (Legacy parity
        //    with `cs_link_manager::deleteLinksBecauseItemIsDeleted`; buzzword
        //    assignments, `member_of`, `material_for`, etc. live in `links`).
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);

        // 4. Soft-delete `link_items` rows referencing this label. Legacy
        //    labels_manager did not touch link_items, but groups use
        //    link_items for user membership (`cs_group_item::addMember`),
        //    so we clean those up for consistency across all rubrics.
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);

        // 5. Soft-delete file-link attachments — labels rarely have them,
        //    but the base class allows it.
        $this->itemDeletionHelper->softDeleteFileLinks($itemId);

        // 6. Soft-delete the shared `items` table row.
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE labels SET creator_id = NULL
                WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE labels SET modifier_id = NULL
                WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
