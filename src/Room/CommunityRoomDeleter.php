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
 * Deletes a `community` room. Replaces `cs_community_item::delete()`.
 * No sub-room cascade: linked project rooms are peers and stay alive —
 * only the bidirectional community↔project `link_items` rows are soft-
 * deleted (same permissive semantics as legacy).
 */
class CommunityRoomDeleter implements RoomDeleter
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
        return RoomType::Community;
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

        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

        // softDeleteLinkItems also tears down the bidirectional community↔project
        // link_items rows (parity with LinkItemManager::deleteLinksBecauseItemIsDeleted).
        $this->rubricDeletionHelper->softDeleteAnnotations($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinks($roomId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($roomId, $deleterId);

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
        $contextId = (int) $this->connection->fetchOne(
            'SELECT context_id FROM room WHERE item_id = :id',
            ['id' => $roomId]
        );
        $this->roomHardDeletionHelper->purgeRoomData($contextId, $roomId);
    }
}
