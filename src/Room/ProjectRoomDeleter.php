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
use LogicException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes a `project` room without delegating to the legacy
 * `cs_project_item::delete()` cascade.
 *
 * Project rooms are the richest room type in the hierarchy and the only
 * one with a real sub-room cascade:
 *
 *  1. **Group rooms** (`type = 'grouproom'`) whose extras point back at
 *     this project — cascaded through {@see GroupRoomDeleter} in silent
 *     mode. `silent = true` suppresses the per-grouproom
 *     {@see WorkspaceDeletedEvent} dispatch so we do not spam one
 *     moderation mail per cascaded grouproom; the project-level mail
 *     emitted at the end of this method is enough.
 *
 *  2. **User rooms** (`type = 'userroom'`) whose extras point back at
 *     this project — cascaded through {@see UserRoomDeleter} in silent
 *     mode. User rooms hang off the parent *project* (their
 *     `LinkedProjectItemID` extras entry), not off the group rooms; the
 *     hierarchy is flat, not nested. This differs from what one might
 *     expect from the grouproom/userroom naming — see
 *     {@see \App\Utils\UserroomService::createUserroom()} for the source
 *     of truth: `$roomContext` passed into `RoomManager::createRoom()` is
 *     the project id, set via `setLinkedProjectItemID()` right after.
 *
 *  3. **Project memberships** (`cs_user_item` rows in this project's
 *     context). Legacy relied on `cs_user_item::delete()` to cascade each
 *     membership into its linked userroom; we cascade userrooms
 *     explicitly in step 2 and then simply mark the membership rows.
 *
 *  4. **Community back-links**: project rooms can be listed as "internal
 *     projects" of one or more community rooms via the community's
 *     `PROJECT_ID_ARRAY` extras blob. That back-reference has to be
 *     removed from every community that points at this project —
 *     delegated to {@see RoomDeletionHelper::nullifyPortalProjectLinks()},
 *     which goes through the legacy API to avoid re-implementing
 *     serialised-PHP mutation in raw SQL. This is the one step where
 *     writes land on rows *outside* the project's context.
 *
 *  5. **Room content** (every rubric item + tasks) — via
 *     {@see RoomContentDeleter}, same as every other room type. Each
 *     rubric deleter still dispatches its own {@see ItemDeletedEvent}
 *     so ES / mail / etherpad cleanup runs per item.
 *
 *  6. **Aux rows on the project entity itself** (annotations, links,
 *     link_items) — via {@see ItemDeletionHelper}.
 *
 *  7. **The `room` and `items` twin rows** get their audit stamps last.
 *
 *  8. **Events**:
 *     - {@see ItemDeletedEvent} → {@see \App\EventSubscriber\ElasticaSubscriber}
 *       removes the `commsy_room` document.
 *     - {@see WorkspaceDeletedEvent} → {@see \App\EventSubscriber\WorkspaceSubscriber}
 *       fans out project + portal moderation mails (branch already
 *       existed in legacy). Suppressed when `$opts->silent` is true —
 *       used by callers that want to fold a project delete into a
 *       higher-level teardown without spamming per-project mails.
 *
 * Legacy parity: `cs_project_item::delete()` did the same steps in a
 * slightly different order (`parent::delete()` first, then the two
 * sub-room loops, then users, then communities, then tasks, then event,
 * then `_delete`, then ES). Reordering here is safe because each step
 * touches disjoint id sets — sub-room ids are collected before the
 * cascade starts, memberships are fetched freshly, and the project's own
 * row is only stamped at the very end.
 */
class ProjectRoomDeleter implements RoomDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
        private readonly RoomDeletionHelper $roomDeletionHelper,
        private readonly RoomContentDeleter $roomContentDeleter,
        private readonly GroupRoomDeleter $groupRoomDeleter,
        private readonly UserRoomDeleter $userRoomDeleter,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function roomType(): RoomType
    {
        return RoomType::Project;
    }

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        // Idempotent guard: preserve the original audit stamp if a
        // second delete path reaches this room (UI double-click, DB-fix
        // scripts, tests). Sub-room cascades are still entered on the
        // first pass, so this only protects the project row itself.
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        // Snapshot the typed item before anything is soft-deleted — the
        // legacy loader filters deleted rows once the `items` twin is
        // stamped, and we need a live handle for both events.
        $typedItem = $this->itemService->getTypedItem($roomId);

        // Silent cascade for all sub-rooms: one mail per project, not
        // per cascaded room. The ids are collected up front so that the
        // cascade itself does not race the list (a group room deleted
        // in step 1 would disappear from step 2's query anyway, but
        // collecting first keeps ordering explicit).
        $silentOpts = $opts->asSilent();

        // 1. Cascade into group rooms of this project.
        $groupRoomIds = $this->roomDeletionHelper->findSubRoomsOfProject($roomId, 'grouproom');
        foreach ($groupRoomIds as $groupRoomId) {
            $this->groupRoomDeleter->softDeleteRoom($groupRoomId, $deleterId, $silentOpts);
        }

        // 2. Cascade into user rooms of this project. These sit as peers
        //    to group rooms — see the class-level note on the flat
        //    hierarchy.
        $userRoomIds = $this->roomDeletionHelper->findSubRoomsOfProject($roomId, 'userroom');
        foreach ($userRoomIds as $userRoomId) {
            $this->userRoomDeleter->softDeleteRoom($userRoomId, $deleterId, $silentOpts);
        }

        // 3. Room content: every rubric item in the project + the task
        //    list. Each rubric deleter dispatches its own events.
        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);

        // 4. Memberships inside the project itself. The cascade into
        //    each member's linked userroom already happened in step 2,
        //    so this is a pure membership-row update.
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        // 5. Auxiliary rows on the project entity itself.
        $this->itemDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->itemDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

        // 6. Remove this project from the `PROJECT_ID_ARRAY` extras of
        //    every community that currently lists it. Only step that
        //    mutates rows outside the project's own context.
        $this->roomDeletionHelper->nullifyPortalProjectLinks($roomId);

        // 7. Soft-delete the `room` row itself.
        $this->connection->executeStatement(
            'UPDATE room
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :roomId',
            ['deleterId' => $deleterId, 'roomId' => $roomId]
        );

        // 8. And the shared `items` twin row.
        $this->itemDeletionHelper->softDeleteItemsRow($roomId, $deleterId);

        // 9. ES cleanup for the `commsy_room` document.
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 10. Moderation mails (project + portal) via the shared
        //     workspace event. Suppressed on silent.
        if (!$opts->silent && $typedItem instanceof \cs_room_item) {
            $this->eventDispatcher->dispatch(new WorkspaceDeletedEvent($typedItem));
        }
    }

    public function hardDeleteRoom(int $roomId): void
    {
        // See UserRoomDeleter::hardDeleteRoom — lands with RoomHardDeleter.
        throw new LogicException(
            'ProjectRoomDeleter::hardDeleteRoom() is not implemented yet — see RoomHardDeleter (upcoming).'
        );
    }
}
