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
 * Deletes a `project` room. Replaces `cs_project_item::delete()`.
 * The only room type with a real sub-room cascade: group rooms and user
 * rooms both hang off the project (flat hierarchy, not nested — see
 * {@see \App\Utils\UserroomService::createUserroom()}).
 */
class ProjectRoomDeleter implements RoomDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly RoomDeletionHelper $roomDeletionHelper,
        private readonly RoomContentDeleter $roomContentDeleter,
        private readonly GroupRoomDeleter $groupRoomDeleter,
        private readonly UserRoomDeleter $userRoomDeleter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RoomHardDeletionHelper $roomHardDeletionHelper,
    ) {}

    public function roomType(): RoomType
    {
        return RoomType::Project;
    }

    public function softDeleteRoom(int $roomId, int $deleterId, RoomDeletionOptions $opts): void
    {
        // Idempotency guard: preserve the original audit stamp.
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        // Snapshot before stamping — legacy loader filters deleted rows.
        $typedItem = $this->itemService->getTypedItem($roomId);

        // Silent sub-room cascade: one mail per project, not per sub-room.
        $silentOpts = $opts->asSilent();

        $groupRoomIds = $this->roomDeletionHelper->findSubRoomsOfProject($roomId, 'grouproom');
        foreach ($groupRoomIds as $groupRoomId) {
            $this->groupRoomDeleter->softDeleteRoom($groupRoomId, $deleterId, $silentOpts);
        }

        $userRoomIds = $this->roomDeletionHelper->findSubRoomsOfProject($roomId, 'userroom');
        foreach ($userRoomIds as $userRoomId) {
            $this->userRoomDeleter->softDeleteRoom($userRoomId, $deleterId, $silentOpts);
        }

        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        $this->rubricDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

        // Remove this project from every community's PROJECT_ID_ARRAY extras —
        // the only step that mutates rows outside the project's context.
        $this->roomDeletionHelper->nullifyPortalProjectLinks($roomId);

        $this->connection->executeStatement(
            'UPDATE room
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :roomId',
            ['deleterId' => $deleterId, 'roomId' => $roomId]
        );

        $this->rubricDeletionHelper->softDeleteItemsRow($roomId, $deleterId);

        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        if (!$opts->silent && $typedItem instanceof \cs_room_item) {
            $this->eventDispatcher->dispatch(new WorkspaceDeletedEvent($typedItem));
        }
    }

    public function hardDeleteRoom(int $roomId): void
    {
        // Sub-rooms carry their own deletion_date and are purged independently
        // by RoomHardDeleter::hardDeleteRoomsOlderThan().
        $contextId = (int) $this->connection->fetchOne(
            'SELECT context_id FROM room WHERE item_id = :id',
            ['id' => $roomId]
        );
        $this->roomHardDeletionHelper->purgeRoomData($contextId, $roomId);
    }
}
