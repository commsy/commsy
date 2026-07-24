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

use App\Room\RoomType;
use App\Rubric\RubricDeletionHelper;
use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_environment;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Shared low-level soft-delete primitives for room-wide data that has no
 * dedicated `RubricDeleter` — tasks, labels, memberships, plus the
 * room-specific extras-blob edge cases. Complements {@see RubricDeletionHelper}.
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
     * Soft-deletes every task in the room plus their aux rows, and flips
     * `status = 'CLOSED'` so moderator UIs stop surfacing ghost rows.
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
     * Soft-deletes every label (topic, buzzword, group, institution, tag)
     * in the room, along with their aux rows. Unlike legacy — which only
     * cleaned them on hard-delete — we apply cleanup on soft-delete too,
     * to keep ES and the labels table consistent with the room lifecycle.
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
     * Soft-deletes every membership row in the room (not the portal-scoped
     * account rows) plus their aux rows.
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
     * Soft-deletes the group-as-label row mirrored onto a group room via
     * the `GROUP_ITEM_ID` extras entry.
     *
     * Legacy-Boundary: isolates cs_grouproom_item extras-blob access;
     * dissolves with the storage-abstraction ticket.
     */
    public function softDeleteLinkedGroupEntity(int $groupRoomId, int $deleterId): void
    {
        $manager = $this->legacyEnvironment->getManager(RoomType::GroupRoom->value);
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
     * Returns ids of all live sub-rooms (grouproom / userroom) of the given
     * project, matched via the serialised-PHP `PROJECT_ROOM_ITEM_ID` extras
     * entry. Parity with legacy `cs_grouproom_manager`/`cs_userroom_manager`
     * `_buildQuery()`.
     *
     * @return int[]
     */
    public function findSubRoomsOfProject(int $projectRoomId, string $roomType): array
    {
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
     * Removes the project id from the `PROJECT_ID_ARRAY` extras blob on
     * every community room that references it. Delegates to the legacy
     * API since raw SQL would have to rebuild the serialised-PHP blob.
     *
     * Legacy-Boundary: isolates cs_community_item extras-blob mutation;
     * dissolves with the storage-abstraction ticket.
     */
    public function nullifyPortalProjectLinks(int $projectRoomId): void
    {
        $manager = $this->legacyEnvironment->getManager(RoomType::Project->value);
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
