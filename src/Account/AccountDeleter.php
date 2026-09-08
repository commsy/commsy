<?php

namespace App\Account;

use App\Entity\Account;
use App\Entity\User;
use App\Message\DeleteAccountMessage;
use App\Repository\UserRepository;
use App\Room\PrivateRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Services\LegacyEnvironment;
use App\User\UserListBuilder;
use App\User\UserMembershipDeleter;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class AccountDeleter
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly UserListBuilder $userListBuilder,
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly UserMembershipDeleter $membershipDeleter,
        private readonly PrivateRoomDeleter $privateRoomDeleter,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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
        $this->deletePrivateRoom($plan);
        $stragglers = $this->sweepStragglers($account, $plan->deleterId);

        // FK `user.account_id` is declared ON DELETE SET NULL — Doctrine
        // removing the account row makes the DB null out every surviving
        // (soft-deleted) user reference automatically.
        $this->entityManager->remove($account);

        return [
            'users_from_builder' => $plan->countFromGraph,
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

        $userList = $this->userListBuilder
            ->fromAccount($account)
            ->withProjectRoomUser()
            ->withCommunityRoomUser()
            ->withUserRoomUser()
            ->withPrivateRoomUser()
            ->getList();

        // The legacy UserListBuilder derives its context set from the official
        // portal-user membership graph and only returns rows reachable from
        // there. Any user row in this portal that matches (username, auth_source)
        // but lives outside that graph — typical for historical inconsistencies
        // with account_id IS NULL or pointing to a different account — would
        // otherwise survive the delete and later get adopted by a new
        // same-username signup.
        $sweepUsers = $this->collectOrphansForAccount($account, $userList);

        return new AccountDeletionPlan(
            // Audit stamp: portal user's own item id (parity with cs_user_item::delete()).
            deleterId: (int) ($portalUser?->getItemID() ?? 0),
            membershipItemIds: [
                ...array_map('intval', $userList->getIDArray()),
                ...array_map(static fn (User $orphan): int => $orphan->getItemId(), $sweepUsers),
            ],
            portalUserItemId: $portalUser !== null ? (int) $portalUser->getItemID() : null,
            privateRoomId: $this->resolvePrivateRoomId($portalUser),
            countFromGraph: $userList->getCount(),
            countFromSweep: count($sweepUsers),
        );
    }

    /**
     * `getRelatedOwnRoomForUser()` inner-joins on `user.deletion_date IS NULL`,
     * so this only answers while the membership rows are still alive.
     */
    private function resolvePrivateRoomId(?cs_user_item $portalUser): ?int
    {
        if ($portalUser === null) {
            return null;
        }

        $privateRoom = $this->legacyEnvironment
            ->getPrivateRoomManager()
            ->getRelatedOwnRoomForUser($portalUser, $portalUser->getContextID());

        return $privateRoom !== null ? (int) $privateRoom->getItemID() : null;
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

    private function deletePrivateRoom(AccountDeletionPlan $plan): void
    {
        if ($plan->privateRoomId === null) {
            return;
        }

        $this->privateRoomDeleter->softDeleteRoom(
            $plan->privateRoomId,
            $plan->deleterId,
            RoomDeletionOptions::forAccountDelete()
        );
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
     * Returns user-table rows in the portal that match the account's
     * (username, auth_source) and were NOT already collected via the legacy
     * UserListBuilder — i.e. the orphan delta the sweep is meant to catch.
     *
     * @return User[]
     */
    private function collectOrphansForAccount(Account $account, cs_list $legacyList): array
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

        $knownItemIds = [];
        foreach ($legacyList as $legacyUser) {
            /** @var cs_user_item $legacyUser */
            $knownItemIds[(int) $legacyUser->getItemID()] = true;
        }

        return array_values(array_filter(
            $sweep,
            static fn (User $u) => !isset($knownItemIds[$u->getItemId()])
        ));
    }
}
