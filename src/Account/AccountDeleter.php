<?php

namespace App\Account;

use App\Entity\Account;
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

        // Audit stamp: the portal user's own item id mirrors legacy
        // `cs_user_item::delete()` when the account deletes itself. Falls
        // back to 0 when the portal user is unresolvable (data-fix paths).
        $deleterId = (int) ($portalUser?->getItemID() ?? 0);

        // Resolve the account's private room up front — the actual
        // PrivateRoomDeleter call happens at the bottom of this method,
        // after all memberships are soft-deleted. It has to be resolved
        // *here* because `PrivateRoomManager::getRelatedOwnRoomForUser()`
        // inner-joins on `user.deletion_date IS NULL`: once any of the
        // account's user rows are stamped (the private-room user row in
        // the loop below, or the portal user row after it), the lookup
        // would no longer find the room and it would silently orphan.
        //
        // Legacy parity: `cs_user_item::delete()` cascaded into
        // `getOwnRoom()->delete()` when the deleted row was the
        // portal-level user. `UserMembershipDeleter` keeps its scope
        // to a single membership row, so we replicate that cascade
        // explicitly via `PrivateRoomDeleter` at the end of this method.
        $privateRoomId = null;
        if ($portalUser !== null) {
            $privateRoom = $this->legacyEnvironment
                ->getPrivateRoomManager()
                ->getRelatedOwnRoomForUser($portalUser, $portalUser->getContextID());
            if ($privateRoom !== null) {
                $privateRoomId = (int) $privateRoom->getItemID();
            }
        }

        // Erase user footprint in each context, then soft-delete the
        // membership row via the new deleter (handles tasks, linked
        // userroom, aux rows, ES, and contact-persons refresh).
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

        // NULL account_id on remaining soft-deleted user references
        $usersWithAccountRef = $this->userRepository->findBy(['account' => $account]);
        foreach ($usersWithAccountRef as $userWithAccountRef) {
            $userWithAccountRef->setAccount(null);
        }

        $this->entityManager->remove($account);
        $this->entityManager->flush();

        $this->logger->info('Account {id} deleted successfully', ['id' => $account->getId()]);
    }
}
