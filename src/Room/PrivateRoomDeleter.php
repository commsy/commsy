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

use App\Rubric\RubricDeletionHelper;
use Doctrine\DBAL\Connection;

/**
 * Deletes a `privateroom` (a user's personal "myroom", one per portal)
 * without delegating to the legacy `cs_privateroom_item::delete()` cascade.
 *
 * A private room is stored in the `room` table with `type = 'privateroom'`
 * and owned 1:1 by a portal user — see
 * {@see \cs_privateroom_manager::getRelatedOwnRoomForUser()}. Structurally
 * it behaves like a user room: no sub-rooms, no portal- / community-link
 * bookkeeping, no moderation mail on delete, and it is **not indexed in
 * ElasticSearch** (see {@see \App\EventSubscriber\ElasticaSubscriber}).
 *
 * Legacy parity:
 *  - `cs_privateroom_item::delete()` just called `cs_room_item::_delete()`
 *    on its manager, which soft-deleted the `room` + `items` rows and
 *    touched `link_items`. No special private-room logic existed.
 *  - The sole caller today is {@see \App\Account\AccountMerger}, which
 *    copies content out of the source user's private room and then
 *    soft-deletes the now-empty shell.
 *
 * Behaviour added on top of legacy for uniform cleanup (same pattern as
 * every other deleter in the Rubric/Room layer):
 *  - Room content (rubric items + tasks) is explicitly soft-deleted via
 *    {@see RoomContentDeleter}. In practice {@see AccountMerger} empties
 *    the room first, so the orchestrator will usually be a no-op, but
 *    callers that do not pre-drain (DB fix scripts, tests, future code
 *    paths) still get a clean wipe.
 *  - The portal-scoped owner row (cs_user_item for this private room) is
 *    soft-deleted via {@see RoomDeletionHelper::softDeleteRoomMemberships()}.
 *    The account itself is not touched — that lives on the `accounts`
 *    table and is the caller's responsibility.
 *
 * `WorkspaceDeletedEvent` is **not** dispatched for private rooms: legacy
 * only dispatched it from project / community items.
 */
class PrivateRoomDeleter implements RoomDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly RoomDeletionHelper $roomDeletionHelper,
        private readonly RoomContentDeleter $roomContentDeleter,
        private readonly RoomHardDeletionHelper $roomHardDeletionHelper,
    ) {}

    public function roomType(): RoomType
    {
        return RoomType::PrivateRoom;
    }

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        // Idempotent guard: a second call on an already soft-deleted room
        // must be a no-op so the original `deleter_id` / `deletion_date`
        // audit stamp is preserved. Realistic triggers for private rooms
        // are overlapping call paths — AccountMerger drops the source
        // room, AccountDeleter may reach the same user shortly after, and
        // DB-fix scripts / tests can re-enter on already-deleted rows.
        // Legacy had no such guard and would happily overwrite the stamp.
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        // 1. Content: every rubric item in this room plus the task list.
        //    For AccountMerger the room is typically empty by now; for
        //    DB-fix / test callers this is the only path that cleans up
        //    items whose creator is no longer a member of the room.
        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);

        // 2. The portal user's membership row inside their own private
        //    room (cs_user_item with context_id = $roomId — not the
        //    portal-level account row on the `accounts` table).
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        // 3. Auxiliary rows attached to the room entity itself. Order
        //    mirrors the per-item cleanup in every RubricDeleter.
        $this->rubricDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

        // 4. Soft-delete the `room` row itself. No per-type extras blob
        //    cleanup — private rooms carry only newsletter / display
        //    preferences which are harmless to leave serialised on a
        //    soft-deleted row.
        $this->connection->executeStatement(
            'UPDATE room
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :roomId',
            ['deleterId' => $deleterId, 'roomId' => $roomId]
        );

        // 5. Finally the shared `items` twin row.
        $this->rubricDeletionHelper->softDeleteItemsRow($roomId, $deleterId);
    }

    public function hardDeleteRoom(int $roomId): void
    {
        // Same shape as UserRoomDeleter::hardDeleteRoom — no type-specific
        // work. The shared helper handles the full cascade.
        $contextId = (int) $this->connection->fetchOne(
            'SELECT context_id FROM room WHERE item_id = :id',
            ['id' => $roomId]
        );
        $this->roomHardDeletionHelper->purgeRoomData($contextId, $roomId);
    }
}
