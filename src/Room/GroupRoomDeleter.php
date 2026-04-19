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
use App\Rubric\RubricDeletionHelper;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes a `grouproom` (workspace owned by a group inside a project room)
 * without delegating to the legacy `cs_grouproom_item::delete()` cascade.
 *
 * Structurally different from user / private rooms in three ways:
 *  1. A group room has a **mirrored label entity** in the `labels` table
 *     (`GROUP_ITEM_ID` extra on the `items` row), which must be soft-
 *     deleted alongside the room — handled via
 *     {@see RoomDeletionHelper::softDeleteLinkedGroupEntity()}.
 *  2. The room **is indexed in ES** under the shared `commsy_room` index.
 *     The generic {@see \App\EventSubscriber\ElasticaSubscriber::onItemDeleted}
 *     handler now recognises the `grouproom` type (see commit body) so a
 *     regular `ItemDeletedEvent` dispatch is enough.
 *  3. Delete **emits moderation mails** (group + project + portal). Legacy
 *     sent these synchronously inline from
 *     `cs_grouproom_item::delete()`; the modernised path dispatches
 *     {@see WorkspaceDeletedEvent} and lets
 *     {@see \App\EventSubscriber\WorkspaceSubscriber} fan out — same event
 *     already used by project / community rooms. `silent = true` on the
 *     options bag skips the event (legacy's `$silent` parameter).
 *
 * Sub-rooms are **not** a concern here: user rooms are direct children of
 * the project room, not the group room (their `context_id` points at the
 * project, not the grouproom — see {@see \App\Utils\UserroomService}).
 * The cascade into user rooms therefore lives in
 * {@see ProjectRoomDeleter}.
 *
 * Legacy parity:
 *  - `cs_grouproom_item::delete()` performed: parent delete → task loop →
 *    mail-to-moderation (if !silent) → `_delete($projectManager)` → delete
 *    linked group → ES removal from `commsy_room`.
 *  - The `cs_project_item` (not `cs_grouproom_manager`) was passed to
 *    `_delete` — a legacy quirk that has no effect on the stored rows
 *    (both managers share the same `room` table) and is not reproduced
 *    here.
 */
class GroupRoomDeleter implements RoomDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly RoomDeletionHelper $roomDeletionHelper,
        private readonly RoomContentDeleter $roomContentDeleter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RoomHardDeletionHelper $roomHardDeletionHelper,
    ) {}

    public function roomType(): RoomType
    {
        return RoomType::GroupRoom;
    }

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        // Idempotent guard: preserve the original audit stamp against
        // overlapping call paths (project-room cascade and a direct
        // UI delete racing, DB-fix scripts, tests).
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        // Grab the typed item *before* anything is soft-deleted — the
        // legacy loader filters deleted rows once the `items` twin is
        // stamped, and we need a live handle for both events below.
        $typedItem = $this->itemService->getTypedItem($roomId);

        // 1. Content: every rubric item in the room plus the task list.
        //    Each rubric deleter dispatches its own ItemDeletedEvent so
        //    ES / mail / etherpad cleanup runs as usual.
        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);

        // 2. User memberships inside the room.
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        // 3. Auxiliary rows attached to the room entity itself.
        $this->rubricDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

        // 4. Mirrored group entity in the `labels` table — must die with
        //    the room so the group list in the parent project is cleaned.
        $this->roomDeletionHelper->softDeleteLinkedGroupEntity($roomId, $deleterId);

        // 5. Soft-delete the `room` row itself.
        $this->connection->executeStatement(
            'UPDATE room
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :roomId',
            ['deleterId' => $deleterId, 'roomId' => $roomId]
        );

        // 6. And the shared `items` twin row.
        $this->rubricDeletionHelper->softDeleteItemsRow($roomId, $deleterId);

        // 7. ES cleanup for the `commsy_room` document.
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 8. Moderation mails (group + project + portal) via the shared
        //    workspace event. Skipped when the caller asks for silence —
        //    typically the project-room cascade, which sends its own
        //    project-level mail and should not spam per-grouproom mails.
        if (!$opts->silent && $typedItem instanceof \cs_room_item) {
            $this->eventDispatcher->dispatch(new WorkspaceDeletedEvent($typedItem));
        }
    }

    public function hardDeleteRoom(int $roomId): void
    {
        // The mirrored group-as-label entity was soft-deleted alongside
        // the room (see softDeleteLinkedGroupEntity). Its physical purge
        // happens automatically when the *parent project room* is hard-
        // deleted: the label row sits in `labels` with context_id = the
        // parent project, which the parent's shared-helper run removes.
        // So the grouproom's own hard-delete does not need extra steps.
        $contextId = (int) $this->connection->fetchOne(
            'SELECT context_id FROM room WHERE item_id = :id',
            ['id' => $roomId]
        );
        $this->roomHardDeletionHelper->purgeRoomData($contextId, $roomId);
    }
}
