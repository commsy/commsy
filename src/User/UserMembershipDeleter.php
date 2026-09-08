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

use App\Entity\Account;
use App\Event\ItemDeletedEvent;
use App\Message\RefreshRoomContactPersonsMessage;
use App\Repository\AccountsRepository;
use App\Room\RoomDeletionOptions;
use App\Room\UserRoomDeleter;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\UserContentDeleter;
use App\Utils\ItemService;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Soft-deletes a single `cs_user_item` row (a room membership, not an
 * account — accounts are owned by AccountDeleter). Replaces the
 * `cs_user_item::delete()` cascade: tasks, the person's content footprint
 * in the room, linked userroom (on project memberships), aux rows, ES
 * cleanup, async contact-persons refresh.
 */
class UserMembershipDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly UserDeletionHelper $userDeletionHelper,
        private readonly UserRoomDeleter $userRoomDeleter,
        private readonly UserContentDeleter $userContentDeleter,
        private readonly AccountsRepository $accountsRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
    ) {}

    /**
     * @param Account|null $account       Overrides the account resolved from
     *                                    the row itself. AccountDeleter passes
     *                                    it so sweep-only orphan rows with
     *                                    `account_id IS NULL` still follow the
     *                                    account's own deletion strategy.
     * @param bool         $eraseContent  Set to false when the person's
     *                                    content must survive the membership
     *                                    because it has been handed to another
     *                                    identity — see AccountMerger.
     */
    public function softDeleteMembership(
        int $userItemId,
        int $deleterId,
        ?Account $account = null,
        bool $eraseContent = true,
    ): void {
        // Idempotency guard: preserve the original audit stamp.
        $row = $this->connection->fetchAssociative(
            'SELECT context_id, portal_id, account_id FROM user
                WHERE item_id = :id AND deletion_date IS NULL',
            ['id' => $userItemId]
        );
        if ($row === false) {
            return;
        }
        $contextId = (int) $row['context_id'];

        // Snapshot before stamping — legacy loader filters deleted rows.
        $typedItem = $this->itemService->getTypedItem($userItemId);

        // Before the content footprint: eraseUserFootprint() nullifies
        // `tasks.creator_id`, after which the person's own tasks can no
        // longer be found by creator. Closing them first keeps the
        // membership-level behaviour identical in both strategies.
        $this->userDeletionHelper->deleteUserTasks($userItemId, $contextId, $deleterId);

        if ($eraseContent) {
            $this->eraseContentFootprint($userItemId, $contextId, (int) $row['portal_id'], $row['account_id'], $account);
        }

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
     * Applies the account's deletion strategy to everything the person
     * authored in this room — main entries deleted (CASCADE) or left in
     * place (KEEP), sub-entries redacted, references nullified.
     *
     * Portal-scoped rows are skipped: their `context_id` is the portal id,
     * where a cascade would reach portal-wide entries (time pulses,
     * portal-level material) that are shared vocabulary rather than the
     * person's own room content.
     *
     * The row's own two columns decide that, not a lookup in `portal` —
     * portal ids and item ids share no sequence, so a room's `item_id` can
     * collide with an unrelated portal id (see the workaround in ItemVoter).
     */
    private function eraseContentFootprint(
        int $userItemId,
        int $contextId,
        int $portalId,
        int|string|null $rowAccountId,
        ?Account $account,
    ): void {
        if ($contextId === $portalId) {
            return;
        }

        if ($account === null && $rowAccountId !== null) {
            $account = $this->accountsRepository->find((int) $rowAccountId);
        }

        $this->userContentDeleter->eraseUserFootprint($userItemId, $contextId, $account);
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
