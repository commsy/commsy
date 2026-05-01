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
 * Deletes a `privateroom` (a user's personal "myroom", one per portal).
 * Replaces `cs_privateroom_item::delete()`. Structurally like a user
 * room — no sub-rooms, no link bookkeeping, no mail, not indexed in ES.
 * Primarily called by AccountMerger / AccountDeleter.
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
        // Idempotency guard: preserve the original audit stamp.
        $alive = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM room WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $roomId]
        );
        if (!$alive) {
            return;
        }

        $this->roomContentDeleter->softDeleteAllContent($roomId, $deleterId);
        $this->roomDeletionHelper->softDeleteRoomMemberships($roomId, $deleterId);

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
