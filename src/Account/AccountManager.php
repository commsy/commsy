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

namespace App\Account;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use cs_room_item;
use cs_user_item;
use cs_user_manager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AccountManager
{
    private cs_environment $legacyEnvironment;

    /**
     * AccountManager constructor.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        private UserService $userService,
        private SessionInterface $session
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function propagateUsernameChange(Account $account, cs_user_item $user, string $username): bool
    {
        $account->setUsername($username);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        /** @var cs_user_manager $userManager */
        $userManager = $this->legacyEnvironment->getUserManager();

        return $userManager->changeUserID($username, $user);
    }

    public function propagateAccountDataToProfiles(Account $account): void
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

            /*
             * TODO: This is still very slow when changes occur, but will drastically improve login performance in
             * most of the "normal" cases
             */
            foreach ($relatedUsers as $relatedUser) {
                /** @var cs_user_item $relatedUser */
                if ($relatedUser->getFirstname() !== $account->getFirstname() ||
                    $relatedUser->getLastname() !== $account->getLastname() ||
                    $relatedUser->getEmail() !== $account->getEmail()
                ) {
                    $relatedUser->setFirstname($account->getFirstname());
                    $relatedUser->setLastname($account->getLastname());
                    $relatedUser->setEmail($account->getEmail());

                    $relatedUser->save();
                }
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
                if ($this->accountIsLastModeratorForRoom($room, $account)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function accountIsLastModeratorForRoom(cs_room_item $room, Account $account): bool
    {
        $roomModeratorIds = $room->getModeratorList()->getIDArray();
        $userInContext = $this->userService->getUserInContext($account, $room->getItemID());

        return ((is_countable($roomModeratorIds) ? count($roomModeratorIds) : 0) === 1) && $userInContext && $userInContext->isModerator();
    }

    public function getAccount(cs_user_item $user, int $portalId): ?Account
    {
        $accountRepository = $this->entityManager->getRepository(Account::class);
        $authSource = $this->entityManager->getRepository(AuthSource::class)->find($user->getAuthSource());

        return $accountRepository->findOneByCredentials($user->getUserID(), $portalId, $authSource);
    }

    public function getPortal(Account $account): ?Portal
    {
        $portalRepository = $this->entityManager->getRepository(Portal::class);

        return $portalRepository->find($account->getContextId());
    }

    public function delete(Account $account)
    {
        // NOTE: normally, we'd fire an `AccountDeletedEvent` here; however, this is actually done in the legacy code:
        // `cs_user_manager->delete()` will fire an `AccountDeletedEvent` for each user object
        $portalUser = $this->userService->getPortalUser($account);

        if ($portalUser) {
            $userList = $portalUser->getRelatedUserList();
            foreach ($userList as $user) {
                /* @var $user cs_user_item */
                $user->delete();
            }

            $this->entityManager->remove($account);
            $this->entityManager->flush();

            $portalUser->delete();
        }
    }

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

    public function unlock(Account $account)
    {
        $account->setLocked(false);
        $account->setActivityState(Account::ACTIVITY_ACTIVE);
        $account->setActivityStateUpdated(null);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    public function updateUserLocale(Account $account, string $locale): void
    {
        $account->setLanguage($locale);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // Update the user's session here too (normally done on login)
        // This will affect the translation language in cs_environment::getSelectedLanguage.
        $this->session->set('_locale', $account->getLanguage());
    }

    public function renewActivityUpdated(Account $account, bool $flush = true): void
    {
        $account->setActivityStateUpdated(new DateTime());
        $this->entityManager->persist($account);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function resetInactivity(
        Account $account,
        bool $resetLastLogin = true,
        bool $resetActivityState = true,
        bool $flush = true
    ): void {
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

    public function resetInactivityToPreviousNonNotificationState(): void
    {
        $accountRepository = $this->entityManager->getRepository(Account::class);

        $accountRepository->updateActivity(Account::ACTIVITY_IDLE_NOTIFIED, Account::ACTIVITY_IDLE);
        $accountRepository->updateActivity(Account::ACTIVITY_ACTIVE_NOTIFIED, Account::ACTIVITY_ACTIVE);
    }
}
