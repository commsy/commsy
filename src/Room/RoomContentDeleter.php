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
 * Orchestrates soft-deletion of *all* rubric content inside a room.
 *
 * Counterpart to {@see \App\Rubric\UserContentDeleter} which erases a single
 * user's footprint. Where the user-footprint path iterates items created by
 * one particular user, this orchestrator iterates *every* still-alive item
 * in a given room — including items whose creator already left the room
 * and therefore never triggered an `AccountDeletedEvent`-based cleanup.
 *
 * The concrete per-room-type cleanup (sub-rooms, memberships, linked
 * group entities, portal-links, `items`-row of the room itself, event
 * dispatch) lives in the matching {@see RoomDeleter} implementation;
 * this class is content-only.
 *
 * Order of cleanup inside a room (all soft-delete):
 *   1. Every rubric's top-level items via its registered `RubricDeleter`.
 *      `LabelDeleter` is included here — it covers the full `cs_label_item`
 *      hierarchy (topic / hashtag / buzzword / timepulse / institution /
 *      group), so room-wide label cleanup falls out of the generic loop.
 *   2. Tasks (room-wide, not a rubric) via {@see RoomDeletionHelper}.
 *
 * Iteration is per-item rather than bulk-UPDATE on purpose: each
 * `RubricDeleter::softDeleteItem()` dispatches an `ItemDeletedEvent` so
 * ElasticaSubscriber can remove the document from ES, moderation mails
 * fire, etherpad cleanup runs, …. Collapsing it into a bulk UPDATE would
 * leave those side-effects unperformed and desync the search index.
 *
 * Rooms with tens of thousands of items therefore pay a linear cost;
 * that was already true under the legacy per-user cascade and is noted
 * in the top-level plan as an out-of-scope candidate for an async queue.
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
     * Soft-deletes every rubric item in `$roomId` plus the room-wide task
     * list. Safe to call on a room that is being hard-deleted next: items
     * already soft-deleted are idempotently skipped by the per-rubric
     * `findItemIdsInContext()` filters (`deletion_date IS NULL`).
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

        // Tasks are auxiliary, not a rubric — clean them up separately.
        // (The room-wide primitive also flips `status = 'CLOSED'` so
        // moderator UIs stop surfacing them.)
        $this->roomDeletionHelper->softDeleteRoomTasks($roomId, $deleterId);
    }
}
