<?php


namespace App\Account;


use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use cs_user_item;
use cs_user_manager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AccountManager
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * AccountManager constructor.
     * @param EntityManagerInterface $entityManager
     * @param LegacyEnvironment $legacyEnvironment
     * @param UserService $userService
     * @param SessionInterface $session
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        UserService $userService,
        SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
        $this->session = $session;
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
    public function propgateAccountDataToProfiles(Account $account): void
    {
        /*
         * This is a real gotcha. When the legacy code persists a new user, it will only create a private room
         * if the legacy environment portal id matches the user context id. We force this behaviour by setting
         * it here explicitly.
         */
        $this->legacyEnvironment->setCurrentPortalID($account->getContextId());

        $portalUser = $this->userService->getPortalUser($account);
        if ($portalUser) {
            $relatedUsers = $portalUser->getRelatedUserList();
            $relatedUsers->add($portalUser);

            foreach ($relatedUsers as $relatedUser) {
                /** @var cs_user_item $relatedUser */
                $relatedUser->setFirstname($account->getFirstname());
                $relatedUser->setLastname($account->getLastname());
                $relatedUser->setEmail($account->getEmail());

                $relatedUser->save();
            }
        }
    }

    public function isLastModerator(Account $account): bool
    {
        $projectManager = $this->legacyEnvironment->getProjectManager();
        $communityManager = $this->legacyEnvironment->getCommunityManager();

        $portalUser = $this->userService->getPortalUser($account);
        if ($portalUser) {
            $roomList = new cs_list();
            $roomList->addList($projectManager->getRelatedProjectRooms($portalUser, $portalUser->getContextID()));
            $roomList->addList($communityManager->getRelatedCommunityRooms($portalUser, $portalUser->getContextID()));

            foreach ($roomList as $room) {
                if ($this->userService->userIsLastModeratorForRoom($room, $portalUser)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param cs_user_item $user
     * @param int $portalId
     * @return Account|null
     */
    public function getAccount(cs_user_item $user, int $portalId): ?Account
    {
        $accountRepository = $this->entityManager->getRepository(Account::class);
        $authSource = $this->entityManager->getRepository(AuthSource::class)->find($user->getAuthSource());
        return $accountRepository->findOneByCredentials($user->getUserID(), $portalId, $authSource);
    }

    /**
     * @param Account $account
     * @return Portal|null
     */
    public function getPortal(Account $account): ?Portal
    {
        $portalRepository = $this->entityManager->getRepository(Portal::class);
        return $portalRepository->find($account->getContextId());
    }

    /**
     * @param Account $account
     */
    public function delete(Account $account)
    {
        // NOTE: normally, we'd fire an `AccountDeletedEvent` here; however, this is actually done in the legacy code:
        // `cs_user_manager->delete()` will fire an `AccountDeletedEvent` for each user object
        $portalUser = $this->userService->getPortalUser($account);

        if ($portalUser) {
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

    /**
     * @param Account $account
     */
    public function lock(Account $account)
    {
        $portalUser = $this->userService->getPortalUser($account);

        if ($portalUser) {
            $portalUser->reject();
            $portalUser->save();
        }

        $account->setLocked(true);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    /**
     * @param Account $account
     */
    public function unlock(Account $account)
    {
        $account->setLocked(false);
        $account->setActivityState(Account::ACTIVITY_ACTIVE);
        $account->setActivityStateUpdated(null);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    /**
     * @param Account $account
     * @param string $locale
     */
    public function updateUserLocale(Account $account, string $locale): void
    {
        $account->setLanguage($locale);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // Update the user's session here too (normally done on login)
        // This will affect the translation language in cs_environment::getSelectedLanguage.
        $this->session->set('_locale', $account->getLanguage());
    }

    /**
     * @param Account $account
     * @param bool $flush
     */
    public function renewActivityUpdated(Account $account, bool $flush = true): void
    {
        $account->setActivityStateUpdated(new DateTime());
        $this->entityManager->persist($account);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param Account $account
     * @param bool $resetLastLogin
     * @param bool $resetActivityState
     * @param bool $flush
     */
    public function resetInactivity(
        Account $account,
        bool $resetLastLogin = true,
        bool $resetActivityState = true,
        bool $flush = true
    ) : void
    {
        if ($resetLastLogin) {
            $account->setLastLogin(new DateTime());
        }

        if ($resetActivityState) {
            $account->setActivityState(Account::ACTIVITY_ACTIVE);
            $account->setActivityStateUpdated(null);
        }

        $this->entityManager->persist($account);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     *
     */
    public function resetInactivityToPreviousNonNotificationState(): void
    {
        $accountRepository = $this->entityManager->getRepository(Account::class);

        $accountRepository->updateActivity(Account::ACTIVITY_IDLE_NOTIFIED, Account::ACTIVITY_IDLE);
        $accountRepository->updateActivity(Account::ACTIVITY_ACTIVE_NOTIFIED, Account::ACTIVITY_ACTIVE);
    }
}