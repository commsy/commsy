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

namespace App\Room;

use App\Event\ItemDeletedEvent;
use App\Event\Workspace\WorkspaceDeletedEvent;
use App\Rubric\ItemDeletionHelper;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes a `community` room without delegating to the legacy
 * `cs_community_item::delete()` cascade.
 *
 * Community rooms sit above project rooms in the workspace hierarchy:
 * a project can declare itself "a member of" one or more communities via
 * bidirectional `link_items` rows, and the portal can be configured so
 * every project has to choose a community on creation. That linkage is
 * only enforced at *creation* time — legacy delete semantics are
 * deliberately permissive:
 *
 *  - A community can be deleted while it still has linked project rooms.
 *  - The project rooms themselves stay alive; only the `link_items`
 *    rows representing the community ↔ project relationship are soft-
 *    deleted. After the delete, those projects return an empty
 *    `getCommunityList()` but are otherwise untouched.
 *  - The portal-level "projects must belong to a community" rule is
 *    *not* re-checked — orphaned projects are tolerated as a legacy
 *    quirk. Reattaching them (if the admin cares) is a manual follow-up.
 *
 * `link_items` cleanup falls out of {@see ItemDeletionHelper::softDeleteLinkItems()},
 * which soft-deletes every row where `$roomId` appears on either side —
 * functionally identical to legacy's `LinkItemManager::deleteLinksBecauseItemIsDeleted()`.
 *
 * Structurally therefore a community room is a group room without the
 * mirrored label entity:
 *  - Indexed in ES under the shared `commsy_room` index → dispatch
 *    {@see ItemDeletedEvent}, {@see \App\EventSubscriber\ElasticaSubscriber}
 *    removes the document.
 *  - Emits moderation mails (community + portal) → dispatch
 *    {@see WorkspaceDeletedEvent}, {@see \App\EventSubscriber\WorkspaceSubscriber}
 *    already knows the `cs_community_item` branch.
 *  - No sub-room cascade: project rooms are peers, not children.
 *
 * Legacy parity:
 *  - `cs_community_item::delete()` did exactly the above (event dispatch
 *    was already modernised, only the cascade into the RoomDeleter
 *    plumbing is new).
 *  - `PROJECT_ID_ARRAY` extras on the community row were never cleaned
 *    up on delete — they simply die with the soft-deleted row, same
 *    as we do here.
 */
class CommunityRoomDeleter implements RoomDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly RoomDeletionHelper $roomDeletionHelper,
        private readonly RoomContentDeleter $roomContentDeleter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RoomHardDeletionHelper $roomHardDeletionHelper,
    ) {}

    public function roomType(): RoomType
    {
        return RoomType::Community;
    }

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        // Idempotent guard: preserve original audit stamp against
        // overlapping paths (UI double-click, DB-fix scripts, tests).
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        // Resolve the typed item before anything is stamped; legacy loader
        // filters deleted rows and we need a live handle for both events.
        $typedItem = $this->itemService->getTypedItem($roomId);

        // 1. Rubric content inside the room + tasks.
        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);

        // 2. Memberships inside the community itself.
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        // 3. Auxiliary rows on the community entity itself.
        //
        //    softDeleteLinkItems() is doing double duty here: besides the
        //    community's own outbound links, it also tears down every
        //    bidirectional community ↔ project `link_items` row (this id
        //    sits on either first_item_id or second_item_id), matching
        //    the legacy LinkItemManager::deleteLinksBecauseItemIsDeleted
        //    behaviour. The linked project rooms themselves are left
        //    alive — community delete does not cascade into projects.
        $this->itemDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

        // 4. Soft-delete the `room` row itself. The `PROJECT_ID_ARRAY`
        //    extras blob is intentionally left intact on the soft-deleted
        //    row — legacy never cleaned it up either.
        $this->connection->executeStatement(
            'UPDATE room
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :roomId',
            ['deleterId' => $deleterId, 'roomId' => $roomId]
        );

        // 5. And the shared `items` twin row.
        $this->itemDeletionHelper->softDeleteItemsRow($roomId, $deleterId);

        // 6. ES cleanup for the `commsy_room` document.
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 7. Moderation mails (community + portal) via the shared
        //    workspace event. Suppressed on silent — no natural cascade
        //    path currently targets communities with silent=true, but
        //    we keep the option for DB-fix scripts / tests / future use.
        if (!$opts->silent && $typedItem instanceof \cs_room_item) {
            $this->eventDispatcher->dispatch(new WorkspaceDeletedEvent($typedItem));
        }
    }

    public function hardDeleteRoom(int $roomId): void
    {
        // Community rooms are peers to projects in the hierarchy, so
        // the hard-delete is a straight cascade with no extra clean-up:
        // the `PROJECT_ID_ARRAY` extras blob vanishes with the room row,
        // and linked project rooms (if any survived the soft-delete
        // phase) keep their own lifecycle.
        $contextId = (int) $this->connection->fetchOne(
            'SELECT context_id FROM room WHERE item_id = :id',
            ['id' => $roomId]
        );
        $this->roomHardDeletionHelper->purgeRoomData($contextId, $roomId);
    }
}
