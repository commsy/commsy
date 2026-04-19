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
use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_environment;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Shared low-level deletion primitives for *room-wide* data that has no
 * `RubricDeleter` of its own.
 *
 * Complements {@see RubricDeletionHelper}: that helper operates on a
 * single item (link_items, links, annotations, file_links, items-row),
 * while this one operates on everything keyed by `context_id = $roomId`
 * — tasks, labels (topics / buzzwords / groups / institutions), user
 * memberships — as well as the two genuinely room-specific edge cases:
 * the `cs_community_item` extras blob that tracks linked project rooms,
 * and the `cs_group_item` label row mirrored onto every group room.
 *
 * Hard-delete primitives (removing the room directory, purging reader /
 * hash rows, bulk `DELETE WHERE context_id`) are intentionally *not*
 * part of this helper yet — they land with the {@see RoomHardDeleter}
 * service in a later commit.
 */
class RoomDeletionHelper
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private readonly Connection $connection,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Soft-deletes every task in `$roomId` regardless of creator, plus
     * each task's annotations and auxiliary rows (link_items, links,
     * file_links, items twin). Task rows are flipped to `status = 'CLOSED'`
     * so moderator UIs that list open requests never show ghost rows.
     *
     * Room-wide counterpart to {@see \App\User\UserDeletionHelper::deleteUserTasks}
     * (which is user-wide). Legacy equivalent:
     * `foreach ($room->_getTaskList() as $task) { $task->delete(); }`.
     */
    public function softDeleteRoomTasks(int $roomId, int $deleterId): void
    {
        $taskIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM tasks
                WHERE context_id = :roomId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['roomId' => $roomId]
        ));
        if (empty($taskIds)) {
            return;
        }

        $this->connection->executeStatement(
            "UPDATE tasks
                SET deletion_date = NOW(), deleter_id = :deleterId, status = 'CLOSED'
                WHERE item_id IN (:ids)",
            ['deleterId' => $deleterId, 'ids' => $taskIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        foreach ($taskIds as $taskId) {
            $this->rubricDeletionHelper->softDeleteAnnotations($taskId, $deleterId);
        }

        $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems($taskIds, $deleterId);
    }

    /**
     * Soft-deletes every label (topic, buzzword, group, institution, tag …)
     * in `$roomId`, along with their link_items / links / file_links /
     * items twin rows.
     *
     * Legacy analogue: the `labels` rows are cleaned up by
     * `cs_labels_manager::deleteFromDb()` during hard-delete; on soft-delete
     * the legacy cascade relied on the room item's own cascade not
     * touching them (they stayed around until the room was really gone).
     * We apply the cleanup on soft-delete too, so ES documents and the
     * `labels` table stay consistent with the room's lifecycle.
     */
    public function softDeleteLabelsInContext(int $roomId, int $deleterId): void
    {
        $labelIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM labels
                WHERE context_id = :roomId AND deletion_date IS NULL',
            ['roomId' => $roomId]
        ));
        if (empty($labelIds)) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE labels
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id IN (:ids)',
            ['deleterId' => $deleterId, 'ids' => $labelIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems($labelIds, $deleterId);
    }

    /**
     * Soft-deletes every `cs_user_item` row whose `context_id = $roomId`
     * — i.e. every user membership *in this room*, not the globally-scoped
     * account rows (those live in context 99).
     *
     * Legacy analogue in `cs_project_item::delete()`:
     * `foreach ($this->getUserList() as $user) { $user->delete(); }`.
     * Aux rows (tasks, annotations, file attachments of user profile
     * pages) are cleaned via the shared batch primitive.
     */
    public function softDeleteRoomMemberships(int $roomId, int $deleterId): void
    {
        $userIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM user
                WHERE context_id = :roomId AND deletion_date IS NULL',
            ['roomId' => $roomId]
        ));
        if (empty($userIds)) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE user
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id IN (:ids)',
            ['deleterId' => $deleterId, 'ids' => $userIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems($userIds, $deleterId);
    }

    /**
     * Soft-deletes the `cs_group_item` label row mirrored onto a group
     * room via the legacy `GROUP_ITEM_ID` extras entry.
     *
     * The link between a group room and its matching group-as-label row
     * is stored as a serialised-PHP extras blob on the group room's
     * `items` row. Rather than re-implementing the serialisation parsing
     * in DBAL, we peek at the legacy loader just long enough to discover
     * the id, then soft-delete the label row with the same primitives
     * used for every other label.
     *
     * Legacy analogue: `cs_grouproom_item::delete()` calls
     * `$this->getLinkedGroupItem()?->delete(false)`.
     *
     * **Legacy-Boundary Layer**: isolates the `cs_grouproom_item` extras-
     * blob access to a single call site. Will go away when Ticket G
     * (Storage-Abstraktion / extras-blob aus Legacy ziehen) lands.
     */
    public function softDeleteLinkedGroupEntity(int $groupRoomId, int $deleterId): void
    {
        $manager = $this->legacyEnvironment->getManager(CS_GROUPROOM_TYPE);
        if (!$manager->existsItem($groupRoomId)) {
            return;
        }
        /** @var \cs_grouproom_item|null $groupRoom */
        $groupRoom = $manager->getItem($groupRoomId);
        if ($groupRoom === null) {
            return;
        }

        $linkedGroupId = (int) ($groupRoom->getLinkedGroupItemID() ?? 0);
        if ($linkedGroupId <= 0) {
            return;
        }

        // Already soft-deleted? (labels table is shared with groups.)
        $alreadyDeleted = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM labels
                WHERE item_id = :id AND deletion_date IS NOT NULL',
            ['id' => $linkedGroupId]
        );
        if ($alreadyDeleted) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE labels
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :id',
            ['deleterId' => $deleterId, 'id' => $linkedGroupId]
        );

        $this->rubricDeletionHelper->softDeleteLinkItems($linkedGroupId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinks($linkedGroupId, $deleterId);
        $this->rubricDeletionHelper->softDeleteItemsRow($linkedGroupId, $deleterId);
    }

    /**
     * Returns the ids of all live sub-rooms (`grouproom` / `userroom`) that
     * belong to the given project room, identified by the `PROJECT_ROOM_ITEM_ID`
     * extras entry on each sub-room's `room` row.
     *
     * Legacy parity: `cs_grouproom_manager::_buildQuery()` and
     * `cs_userroom_manager::_buildQuery()` both filter precisely with a
     * serialised-PHP `LIKE` against the same extras blob
     * (`s:20:"PROJECT_ROOM_ITEM_ID";i:<projectId>;`). We replicate the
     * pattern verbatim instead of parsing the blob, because the format is
     * frozen legacy and the LIKE matches the same rows the legacy managers
     * would — including rooms whose `context_id` column holds the portal
     * id rather than the project id (userrooms go via the portal per
     * {@see \App\Room\RoomManager::createRoom}).
     *
     * Deleted sub-rooms are filtered out so the idempotent guard inside
     * the respective sub-room deleter stays consistent with what we
     * already know to skip.
     *
     * @return int[]
     */
    public function findSubRoomsOfProject(int $projectRoomId, string $roomType): array
    {
        // Serialised-PHP LIKE pattern, identical to the one legacy's
        // cs_grouproom_manager / cs_userroom_manager builds.
        $pattern = '%s:20:"PROJECT_ROOM_ITEM_ID";i:' . $projectRoomId . ';%';

        return array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM room
                WHERE type = :type
                  AND deletion_date IS NULL
                  AND extras LIKE :pattern',
            ['type' => $roomType, 'pattern' => $pattern]
        ));
    }

    /**
     * Removes `$projectRoomId` from the `PROJECT_ID_ARRAY` extras blob
     * of every community room that currently references it.
     *
     * The relationship between a community and its "internal" project
     * rooms lives in a serialised-PHP array on the community item's
     * extras column (see `cs_community_item::addProjectID2InternalProjectIDArray`
     * / `removeProjectID2InternalProjectIDArray`). Rebuilding the
     * serialisation in raw SQL is fragile — and touching the extras
     * blob also has to go through the community's `saveWithoutChanging
     * ModificationInformation()` so the modification timestamp is not
     * bumped. So we delegate to the legacy API, which is narrow enough
     * to stay stable even while the rest of the cascade is modernised.
     *
     * Legacy analogue: the community-list loop inside
     * `cs_project_item::delete()`.
     *
     * **Legacy-Boundary Layer**: isolates the `cs_community_item` extras-
     * blob mutation (`PROJECT_ID_ARRAY`) to a single call site. Will go
     * away when Ticket G (Storage-Abstraktion) replaces the serialised-
     * PHP extras blob with a proper schema.
     */
    public function nullifyPortalProjectLinks(int $projectRoomId): void
    {
        $manager = $this->legacyEnvironment->getManager(CS_PROJECT_TYPE);
        if (!$manager->existsItem($projectRoomId)) {
            return;
        }
        /** @var \cs_project_item|null $project */
        $project = $manager->getItem($projectRoomId);
        if ($project === null) {
            return;
        }

        $communities = $project->getCommunityList();
        if (!is_object($communities) || !$communities->isNotEmpty()) {
            return;
        }

        $community = $communities->getFirst();
        while ($community) {
            if ($community instanceof cs_community_item) {
                $community->removeProjectID2InternalProjectIDArray($projectRoomId);
                $community->saveWithoutChangingModificationInformation();
            }
            $community = $communities->getNext();
        }
    }
}
