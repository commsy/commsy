<?php

namespace App\Account;

use App\Entity\Account;
use App\Entity\User;
use App\Message\DeleteAccountMessage;
use App\Repository\UserRepository;
use App\Room\PrivateRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Rubric\UserContentDeleter;
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
        private readonly UserContentDeleter $userContentDeleter,
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

    public function delete(Account $account): void
    {
        $this->logger->info('Deleting account {id}', ['id' => $account->getId()]);

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

        // Audit stamp: portal user's own item id (parity with cs_user_item::delete()).
        $deleterId = (int) ($portalUser?->getItemID() ?? 0);

        // Resolve the private room BEFORE stamping any user rows —
        // getRelatedOwnRoomForUser() inner-joins on user.deletion_date IS NULL,
        // so once the user rows are stamped the lookup would silently orphan.
        $privateRoomId = null;
        if ($portalUser !== null) {
            $privateRoom = $this->legacyEnvironment
                ->getPrivateRoomManager()
                ->getRelatedOwnRoomForUser($portalUser, $portalUser->getContextID());
            if ($privateRoom !== null) {
                $privateRoomId = (int) $privateRoom->getItemID();
            }
        }

        // Phase 1: orphan sweep. The legacy UserListBuilder above derives its
        // context set from the official portal-user membership graph and only
        // returns rows reachable from there. Any user row in this portal that
        // matches (username, auth_source) but lives outside that graph — typical
        // for historical inconsistencies with account_id IS NULL or pointing to
        // a different account — would otherwise survive the delete and later
        // get adopted by a new same-username signup.
        //
        // We sweep them here BEFORE the main loop so eraseUserFootprint runs
        // on every matching row, regardless of how it ended up in the table.
        $sweepUsers = $this->collectOrphansForAccount($account, $userList);

        // Erase footprint per context, then soft-delete the membership row.
        foreach ($userList as $user) {
            /** @var cs_user_item $user */
            $this->userContentDeleter->eraseUserFootprint(
                $user->getItemID(),
                $user->getContextID(),
                $account,
            );
            $this->membershipDeleter->softDeleteMembership(
                (int) $user->getItemID(),
                $deleterId
            );
        }

        // Same treatment for sweep-only orphans. context_id is reconstructed
        // from the entity: getRoom() covers every room user (project, community,
        // group, userroom, privateroom) — Room.item_id IS user.context_id by
        // mapping. getRoom() is NULL only for portal users (no Room entity
        // exists with item_id == portal_id), where context_id == portal_id by
        // construction (see AccountCreatorFacade::persistNewAccount), so the
        // portal fallback returns the correct value.
        foreach ($sweepUsers as $orphan) {
            $contextId = $orphan->getRoom()?->getItemId()
                ?? $orphan->getPortal()?->getId()
                ?? 0;
            $this->userContentDeleter->eraseUserFootprint(
                $orphan->getItemId(),
                $contextId,
                $account,
            );
            $this->membershipDeleter->softDeleteMembership(
                $orphan->getItemId(),
                $deleterId
            );
        }

        if ($portalUser !== null) {
            $this->membershipDeleter->softDeleteMembership(
                (int) $portalUser->getItemID(),
                $deleterId
            );
        }

        if ($privateRoomId !== null) {
            $this->privateRoomDeleter->softDeleteRoom(
                $privateRoomId,
                $deleterId,
                RoomDeletionOptions::forAccountDelete()
            );
        }

        // Safety net: re-run the sweep right before tearing down the account.
        // If anything is found here, it means a row crept in between the first
        // sweep and now (race condition, downstream side-effect we missed, …).
        // We soft-delete it and log loudly — drift = 0 is the invariant we
        // want monitored in Phase 4.
        $stragglers = $this->userRepository->findActiveProfilesByUsernameInPortal(
            $account->getUsername(),
            $account->getAuthSource()->getId(),
            $account->getPortal()->getId(),
        );
        if ($stragglers !== []) {
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
                $this->membershipDeleter->softDeleteMembership(
                    $straggler->getItemId(),
                    $deleterId
                );
            }
        }

        // NULL account_id on remaining soft-deleted user references
        $usersWithAccountRef = $this->userRepository->findBy(['account' => $account]);
        foreach ($usersWithAccountRef as $userWithAccountRef) {
            $userWithAccountRef->setAccount(null);
        }

        $this->entityManager->remove($account);
        $this->entityManager->flush();

        $this->logger->info(
            'Account {id} deleted successfully',
            [
                'id' => $account->getId(),
                'users_from_builder' => $userList->getCount(),
                'users_from_sweep' => count($sweepUsers),
                'stragglers' => count($stragglers),
            ]
        );
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
        $authSource = $account->getAuthSource();
        if ($portal === null || $authSource === null) {
            return [];
        }

        $sweep = $this->userRepository->findActiveProfilesByUsernameInPortal(
            $account->getUsername(),
            $authSource->getId(),
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
