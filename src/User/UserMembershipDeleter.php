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

namespace App\User;

use App\Event\ItemDeletedEvent;
use App\Message\RefreshRoomContactPersonsMessage;
use App\Room\RoomDeletionOptions;
use App\Room\UserRoomDeleter;
use App\Rubric\RubricDeletionHelper;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Soft-deletes a single `cs_user_item` row (a *room membership*) without
 * delegating to the legacy `cs_user_item::delete()` cascade.
 *
 * Note on terminology: a `cs_user_item` is *not* an account — it is a
 * per-room membership row. A single account materialises as one
 * `cs_user_item` per room they are a member of (project, community,
 * grouproom, userroom, …) plus one global "portal user" row in the
 * portal context. Deleting the global portal user is the job of
 * `AccountDeleter`; this service handles the per-room rows.
 *
 * Legacy parity (`cs_user_item::delete`):
 *  - Delete the user's tasks in the same context. Done via
 *    {@see UserDeletionHelper::deleteUserTasks()}.
 *  - Delete the linked user room when the membership lives in a
 *    project context (the user room is "this user's private corner of
 *    the project"). Done via {@see UserRoomDeleter} in silent mode so
 *    the cascade does not spam moderation mails.
 *  - Soft-delete the `user` row + its `items` twin and aux rows
 *    (link_items, links, file_links).
 *  - Dispatch {@see ItemDeletedEvent} so
 *    {@see \App\EventSubscriber\ElasticaSubscriber} removes the
 *    `commsy_user` document.
 *
 * Two pieces of legacy behaviour are handled differently:
 *  - `makeNoContactPerson()` flips the `cs_user_item.is_contact`
 *    column off before the row is soft-deleted. Skipped: soft-deleted
 *    rows are excluded from the contact list anyway, so the flag
 *    mutation is cosmetic on dead rows.
 *  - `cs_room_item::renewContactPersonString()` rebuilds the
 *    denormalised `room.contact_persons` cache string shown on room
 *    cards / room home pages. Replaced by an async dispatch of
 *    {@see RefreshRoomContactPersonsMessage}, handled by
 *    {@see \App\MessageHandler\RefreshRoomContactPersonsHandler}, so
 *    the originating user request returns without waiting for the
 *    moderator-list rebuild + room save.
 */
class UserMembershipDeleter
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly UserDeletionHelper $userDeletionHelper,
        private readonly UserRoomDeleter $userRoomDeleter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function softDeleteMembership(int $userItemId, int $deleterId): void
    {
        // Idempotency guard: if the row is already soft-deleted, leave
        // the original audit stamp alone. Same pattern as the room
        // deleters use.
        $row = $this->connection->fetchAssociative(
            'SELECT context_id FROM user
                WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $userItemId]
        );
        if ($row === false) {
            return;
        }
        $contextId = (int) $row['context_id'];

        // Snapshot the typed item before stamping — the legacy loader
        // filters deleted rows once the `items` twin is marked, and we
        // need a live handle for the ES event.
        $typedItem = $this->itemService->getTypedItem($userItemId);

        // 1. Tasks owned by this user in this context.
        $this->userDeletionHelper->deleteUserTasks($userItemId, $contextId, $deleterId);

        // 2. Linked user room (only ever set on project memberships).
        //    Cascaded silently so the project-level moderation mail is
        //    not joined by a per-userroom one.
        $linkedUserroomId = $this->resolveLinkedUserroomId($typedItem);
        if ($linkedUserroomId > 0) {
            $this->userRoomDeleter->softDeleteRoom(
                $linkedUserroomId,
                $deleterId,
                RoomDeletionOptions::forUserAction()->asSilent()
            );
        }

        // 3. Soft-delete the membership row itself.
        $this->connection->executeStatement(
            'UPDATE user
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :id',
            ['deleterId' => $deleterId, 'id' => $userItemId]
        );

        // 4. Aux rows (link_items, links, file_links, items twin).
        $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems([$userItemId], $deleterId);

        // 5. ES cleanup for the `commsy_user` document.
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 6. Refresh the room's denormalised contact-persons cache
        //    string so the leaver stops appearing in the moderator
        //    widget. Async via Messenger — the user-facing request
        //    does not wait for the rebuild. Handler skips portal /
        //    server contexts on its own, so we dispatch unconditionally.
        $this->messageBus->dispatch(new RefreshRoomContactPersonsMessage($contextId));
    }

    /**
     * Resolves the `LINKED_USERROOM_ITEM_ID` extras entry on the user
     * row. Returns 0 when no userroom is attached. Membership rows in
     * non-project contexts (community, grouproom, userroom, portal)
     * never carry this extra, so the lookup is a fast no-op there.
     */
    private function resolveLinkedUserroomId(?object $typedItem): int
    {
        if (!$typedItem instanceof cs_user_item) {
            return 0;
        }

        return (int) ($typedItem->getLinkedUserroomItemID() ?? 0);
    }
}
