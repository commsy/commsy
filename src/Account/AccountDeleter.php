<?php

namespace App\Account;

use App\Entity\Account;
use App\Message\DeleteAccountMessage;
use App\Repository\UserRepository;
use App\Rubric\UserContentDeleter;
use App\User\UserListBuilder;
use App\Utils\UserService;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AccountDeleter
{
    public function __construct(
        private UserContentDeleter $userContentDeleter,
        private UserListBuilder $userListBuilder,
        private UserService $userService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {}

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

        // Erase user footprint in each context, then soft-delete the user
        foreach ($userList as $user) {
            /** @var cs_user_item $user */
            $this->userContentDeleter->eraseUserFootprint(
                $user->getItemID(),
                $user->getContextID(),
                $account,
            );
            $user->delete();
        }

        $portalUser?->delete();

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
