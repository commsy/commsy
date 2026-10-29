<?php

namespace App\Account;

use App\Entity\Account;
use App\Entity\User;
use App\Message\DeleteAccountMessage;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Room\PrivateRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\User\UserMembershipDeleter;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class AccountDeleter
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly UserMembershipDeleter $membershipDeleter,
        private readonly PrivateRoomDeleter $privateRoomDeleter,
        private readonly RoomRepository $roomRepository,
    ) {
    }

    /**
     * Dispatches async account deletion via Messenger.
     */
    public function dispatch(Account $account): void
    {
        $this->messageBus->dispatch(new DeleteAccountMessage($account->getId()));
    }

    /**
     * Runs the whole deletion atomically. Same shape as
     * {@see AccountMerger::merge()}: the legacy DB layer and the ORM share
     * the connection (`database_connection`), so legacy `performQuery()`
     * writes commit — or roll back — together with the Doctrine ones.
     * Without that, a failure halfway through left an account half deleted
     * while the person was already logged out.
     *
     * `wrapInTransaction()` flushes before committing, and closes the
     * entity manager if the callback throws — the Messenger handler then
     * fails the message rather than continuing on a dead manager.
     */
    public function delete(Account $account): void
    {
        $this->logger->info('Deleting account {id}', ['id' => $account->getId()]);

        /** @var array<string, int> $summary */
        $summary = $this->entityManager->wrapInTransaction(
            fn (): array => $this->deleteMembershipsAndAccount($account)
        );

        $this->logger->info(
            'Account {id} deleted successfully',
            ['id' => $account->getId()] + $summary
        );
    }

    /**
     * @return array<string, int> counts for the deletion log
     */
    private function deleteMembershipsAndAccount(Account $account): array
    {
        $plan = $this->planDeletion($account);

        $this->endMemberships($account, $plan);
        $this->deletePrivateRooms($plan);
        $stragglers = $this->sweepStragglers($account, $plan->deleterId);

        // FK `user.account_id` is declared ON DELETE SET NULL — Doctrine
        // removing the account row makes the DB null out every surviving
        // (soft-deleted) user reference automatically.
        $this->entityManager->remove($account);

        return [
            'users_of_account' => $plan->countOfAccount,
            'users_from_sweep' => $plan->countFromSweep,
            'stragglers' => $stragglers,
        ];
    }

    /**
     * Reads everything the deletion needs, before anything is stamped.
     */
    private function planDeletion(Account $account): AccountDeletionPlan
    {
        $portalUser = null;
        try {
            $portalUser = $this->userService->getPortalUser($account);
        } catch (LogicException) {
        }
        $portalUserItemId = $portalUser !== null ? (int) $portalUser->getItemID() : null;

        // Every live profile of the account, not only those its portal profile
        // leads to: a profile left alive here loses its account with the
        // account row and then blocks the next signup with the same username.
        $profileIds = array_values(array_filter(
            $this->userRepository->findLiveProfileIdsOf($account),
            static fn (int $itemId): bool => $itemId !== $portalUserItemId,
        ));

        // Orphans sharing the username in the portal, which no account claims
        // any more, would be adopted by the next signup with that username.
        $sweepUsers = $this->collectOrphansForAccount($account, $profileIds);

        return new AccountDeletionPlan(
            // Audit stamp: portal user's own item id (parity with cs_user_item::delete()).
            deleterId: (int) ($portalUserItemId ?? 0),
            membershipItemIds: [
                ...$profileIds,
                ...array_map(static fn (User $orphan): int => $orphan->getItemId(), $sweepUsers),
            ],
            portalUserItemId: $portalUserItemId,
            privateRoomIds: $this->roomRepository->findLivePrivateRoomIdsOf($account),
            countOfAccount: count($profileIds),
            countFromSweep: count($sweepUsers),
        );
    }

    /**
     * The content footprint per room is erased inside softDeleteMembership()
     * — the same seam the in-room membership deletions use. Passing the
     * account keeps orphans with account_id IS NULL on its strategy.
     */
    private function endMemberships(Account $account, AccountDeletionPlan $plan): void
    {
        foreach ($plan->membershipItemIds as $membershipItemId) {
            $this->membershipDeleter->softDeleteMembership($membershipItemId, $plan->deleterId, $account);
        }

        // The portal row last, and separately: it is the one membership whose
        // context is the portal, where softDeleteMembership() skips the
        // content cascade.
        if ($plan->portalUserItemId !== null) {
            $this->membershipDeleter->softDeleteMembership(
                $plan->portalUserItemId,
                $plan->deleterId,
                $account
            );
        }
    }

    private function deletePrivateRooms(AccountDeletionPlan $plan): void
    {
        foreach ($plan->privateRoomIds as $privateRoomId) {
            $this->privateRoomDeleter->softDeleteRoom(
                $privateRoomId,
                $plan->deleterId,
                RoomDeletionOptions::forAccountDelete()
            );
        }
    }

    /**
     * A row found here appeared after the plan was read — a concurrent write,
     * or a downstream side-effect nobody accounted for. It is soft-deleted and
     * logged as an error, because the expected count is zero.
     *
     * @return int number of stragglers found
     */
    private function sweepStragglers(Account $account, int $deleterId): int
    {
        $stragglers = $this->userRepository->findActiveOrphansByUsernameInPortal(
            $account->getUsername(),
            $account->getPortal()->getId(),
        );
        if ($stragglers === []) {
            return 0;
        }

        $this->logger->error(
            'AccountDeleter safety net caught stragglers — investigate concurrent writes or missing soft-delete path.',
            [
                'account_id' => $account->getId(),
                'username' => $account->getUsername(),
                'portal_id' => $account->getPortal()->getId(),
                'straggler_item_ids' => array_map(static fn (User $u) => $u->getItemId(), $stragglers),
            ]
        );

        foreach ($stragglers as $straggler) {
            $this->membershipDeleter->softDeleteMembership($straggler->getItemId(), $deleterId, $account);
        }

        return count($stragglers);
    }

    /**
     * Orphan rows in the portal with the account's username that are not
     * among its own profiles already.
     *
     * @param list<int> $profileIds
     *
     * @return User[]
     */
    private function collectOrphansForAccount(Account $account, array $profileIds): array
    {
        $portal = $account->getPortal();
        if ($portal === null) {
            return [];
        }

        $sweep = $this->userRepository->findActiveOrphansByUsernameInPortal(
            $account->getUsername(),
            $portal->getId(),
        );
        if ($sweep === []) {
            return [];
        }

        $knownItemIds = array_fill_keys($profileIds, true);

        return array_values(array_filter(
            $sweep,
            static fn (User $u) => !isset($knownItemIds[$u->getItemId()])
        ));
    }
}
