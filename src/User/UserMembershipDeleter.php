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
 * Soft-deletes a single `cs_user_item` row (a room membership, not an
 * account — accounts are owned by AccountDeleter). Replaces the
 * `cs_user_item::delete()` cascade: tasks, linked userroom (on project
 * memberships), aux rows, ES cleanup, async contact-persons refresh.
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
        // Idempotency guard: preserve the original audit stamp.
        $row = $this->connection->fetchAssociative(
            'SELECT context_id FROM user
                WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $userItemId]
        );
        if ($row === false) {
            return;
        }
        $contextId = (int) $row['context_id'];

        // Snapshot before stamping — legacy loader filters deleted rows.
        $typedItem = $this->itemService->getTypedItem($userItemId);

        $this->userDeletionHelper->deleteUserTasks($userItemId, $contextId, $deleterId);

        // Linked userroom (only set on project memberships). Silent cascade
        // so the project-level mail is not joined by a per-userroom one.
        $linkedUserroomId = $this->resolveLinkedUserroomId($typedItem);
        if ($linkedUserroomId > 0) {
            $this->userRoomDeleter->softDeleteRoom(
                $linkedUserroomId,
                $deleterId,
                RoomDeletionOptions::forUserAction()->asSilent()
            );
        }

        $this->connection->executeStatement(
            'UPDATE user
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :id',
            ['deleterId' => $deleterId, 'id' => $userItemId]
        );

        $this->rubricDeletionHelper->softDeleteAuxiliaryRowsForItems([$userItemId], $deleterId);

        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // Async refresh of the denormalised room.contact_persons cache.
        // Handler skips portal / server contexts on its own.
        $this->messageBus->dispatch(new RefreshRoomContactPersonsMessage($contextId));
    }

    /**
     * Resolves the `LINKED_USERROOM_ITEM_ID` extras entry. Returns 0 when
     * none is attached (non-project memberships never carry this extra).
     */
    private function resolveLinkedUserroomId(?object $typedItem): int
    {
        if (!$typedItem instanceof cs_user_item) {
            return 0;
        }

        return (int) ($typedItem->getLinkedUserroomItemID() ?? 0);
    }
}
