<?php


namespace App\Account;


use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use cs_user_manager;
use Doctrine\ORM\EntityManagerInterface;

class AccountManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var cs_environment
     */
    private $legacyEnvironment;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * AccountManager constructor.
     * @param EntityManagerInterface $entityManager
     * @param LegacyEnvironment $legacyEnvironment
     * @param UserService $userService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        UserService $userService
    ) {
        $this->entityManager = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
    }

    /**
     * @param Account $account
     * @param cs_user_item $user
     * @param string $username
     * @return bool
     */
    public function propagateUsernameChange(Account $account, cs_user_item $user, string $username): bool
    {
        $account->setUsername($username);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        /** @var cs_user_manager $userManager */
        $userManager = $this->legacyEnvironment->getUserManager();
        return $userManager->changeUserID($username, $user);
    }

    /**
     * @param Account $account
     */
    public function delete(Account $account)
    {
        // NOTE: normally, we'd fire an `AccountDeletedEvent` here; however, this is actually done in the legacy code:
        // `cs_user_manager->delete()` will fire an `AccountDeletedEvent` for each user object
        $portalUser = $this->userService->getPortalUser($account);

        $userList = $portalUser->getRelatedUserList();
        foreach ($userList as $user) {
            /** @var $user cs_user_item */
            $user->delete();
        }

        $this->entityManager->remove($account);
        $this->entityManager->flush();

        $portalUser->delete();
    }
}