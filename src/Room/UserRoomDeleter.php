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

use App\Rubric\ItemDeletionHelper;
use Doctrine\DBAL\Connection;

/**
 * Deletes a `userroom` (personal work area inside a project room) without
 * delegating to the legacy `cs_userroom_item::delete()` cascade.
 *
 * User rooms are the simplest room flavour: no sub-rooms cascade into
 * them, no community/project-link bookkeeping, no mirrored label entity,
 * and no moderation-mail on delete. The cleanup is therefore a thin
 * composition of the shared content/membership/auxiliary primitives.
 *
 * Legacy parity:
 *  - `cs_room_item::delete()` soft-deleted annotations attached to the room.
 *  - `cs_userroom_item::delete()` added a `_delete($projectManager)` which
 *    (a) soft-deleted the `room` row, (b) cleaned up `link_items` referencing
 *    the room, and (c) soft-deleted the `items` twin row.
 *  - `deleteFromElastic()` was called but user rooms are **not indexed**
 *    (see {@see \App\EventSubscriber\ElasticaSubscriber}), so that line was
 *    a no-op and we intentionally skip it here.
 *
 * Behaviour added on top of legacy for uniform cleanup (same pattern as
 * every other deleter in the Rubric/Room layer):
 *  - Room content (rubric items + tasks) is explicitly soft-deleted via
 *    {@see RoomContentDeleter}. The legacy cascade reached this only
 *    indirectly through the per-user `AccountDeletedEvent`-driven footprint
 *    erasure — items whose creator had already left the room therefore
 *    stayed orphaned until hard-delete. We close that gap here.
 *  - User memberships in the room are soft-deleted via
 *    {@see RoomDeletionHelper::softDeleteRoomMemberships()}; legacy emulated
 *    this indirectly via `cs_project_item::delete()`'s user loop.
 *
 * `WorkspaceDeletedEvent` is **not** dispatched for user rooms: legacy
 * dispatched it only from project / community items, and
 * {@see \App\EventSubscriber\WorkspaceSubscriber} ignores anything else.
 */
class UserRoomDeleter implements RoomDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly RoomDeletionHelper $roomDeletionHelper,
        private readonly RoomContentDeleter $roomContentDeleter,
        private readonly RoomHardDeletionHelper $roomHardDeletionHelper,
    ) {}

    public function roomType(): RoomType
    {
        return RoomType::UserRoom;
    }

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        // Idempotent guard: a second call on an already soft-deleted room
        // must be a no-op so the original `deleter_id` / `deletion_date`
        // audit stamp is preserved. Realistic triggers are not concurrency
        // races but overlapping call paths on the same room: UI double-
        // clicks, cascading deletes that reach the same room twice
        // (AccountMerge / AccountDelete touching a user's rooms while the
        // owning project is being wiped), DB-fix scripts that retry, and
        // test fixtures that re-delete an already deleted room. Legacy
        // had no such guard and would happily overwrite the stamp — this
        // is a deliberate improvement.
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        // 1. Content: every rubric item in this room plus the task list.
        //    Each rubric deleter dispatches its own ItemDeletedEvent so ES /
        //    mail / etherpad cleanup runs as usual.
        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);

        // 2. User memberships inside the room (cs_user_item rows whose
        //    context_id = $roomId — not the portal-scoped account rows).
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        // 3. Auxiliary rows attached to the room entity itself. Order
        //    mirrors the per-item cleanup in every RubricDeleter.
        $this->itemDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

        // 4. Soft-delete the `room` row itself. No per-type extras blob
        //    cleanup — user rooms carry only `PROJECT_ROOM_ITEM_ID` /
        //    `LINKED_USER_ITEM_ID` back-references, which are harmless
        //    to leave serialised on a soft-deleted row.
        $this->connection->executeStatement(
            'UPDATE room
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :roomId',
            ['deleterId' => $deleterId, 'roomId' => $roomId]
        );

        // 5. Finally the shared `items` twin row.
        $this->itemDeletionHelper->softDeleteItemsRow($roomId, $deleterId);
    }

    public function hardDeleteRoom(int $roomId): void
    {
        // User rooms have no type-specific hard-delete work: no mirrored
        // label entity (unlike group rooms), no community back-links
        // (unlike project rooms), no portal-extras cleanup. The shared
        // helper does everything — FS wipe, reader / hash purge, the
        // 20 per-rubric-table deletes, and the `room` row itself.
        $contextId = (int) $this->connection->fetchOne(
            'SELECT context_id FROM room WHERE item_id = :id',
            ['id' => $roomId]
        );
        $this->roomHardDeletionHelper->purgeRoomData($contextId, $roomId);
    }
}
