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

use App\Rubric\RubricDeleter;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Orchestrates soft-deletion of all rubric content inside a room. Room-wide
 * counterpart to {@see \App\Rubric\UserContentDeleter}. Iterates per item
 * (rather than bulk UPDATE) so each `RubricDeleter` can dispatch its own
 * ItemDeletedEvent for ES / mail / etherpad cleanup.
 */
class RoomContentDeleter
{
    /** @param iterable<RubricDeleter> $deleters */
    public function __construct(
        #[AutowireIterator('app.rubric.deleter')]
        private readonly iterable $deleters,
        private readonly RoomDeletionHelper $roomDeletionHelper,
    ) {}

    /**
     * Soft-deletes every rubric item in the room plus the room-wide task
     * list. Idempotent.
     *
     * @param int $roomId    context id of the room whose content is removed
     * @param int $deleterId user id stamped on all `deleter_id` columns
     */
    public function softDeleteAllContent(int $roomId, int $deleterId): void
    {
        foreach ($this->deleters as $deleter) {
            foreach ($deleter->findItemIdsInContext($roomId) as $itemId) {
                $deleter->softDeleteItem($itemId, $deleterId);
            }
        }

        // Tasks are not a rubric — clean them up separately.
        $this->roomDeletionHelper->softDeleteRoomTasks($roomId, $deleterId);
    }
}
