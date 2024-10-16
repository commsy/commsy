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
use App\User\UserListBuilder;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use cs_room_item;
use cs_user_item;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AccountManager
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        private UserService $userService,
        private RequestStack $requestStack,
        private UserListBuilder $userListBuilder
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function propagateUsernameChange(Account $account, cs_user_item $user, string $username): bool
    {
        $account->setUsername($username);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

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

        try {
            $portalUser = $this->userService->getPortalUser($account);
            $roomList = new cs_list();
            $roomList->addList($projectManager->getRelatedProjectRooms($portalUser, $portalUser->getContextID()));
            $roomList->addList($communityManager->getRelatedCommunityRooms($portalUser, $portalUser->getContextID()));

            foreach ($roomList as $room) {
                if ($this->accountIsLastModeratorForRoom($room, $account)) {
                    return true;
                }
            }
        } catch (Exception) {
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

    public function getAccounts(int $portalId, cs_user_item ...$users): iterable
    {
        foreach ($users as $user) {
            yield $this->getAccount($user, $portalId);
        }
    }

    public function getPortal(Account $account): ?Portal
    {
        $portalRepository = $this->entityManager->getRepository(Portal::class);

        return $portalRepository->find($account->getContextId());
    }

    public function delete(Account $account): void
    {
        $portalUser = null;
        $userList = new cs_list();

        try {
            // NOTE: normally, we'd fire an `AccountDeletedEvent` here; however, this is actually done in the legacy code:
            // `cs_user_manager->delete()` will fire an `AccountDeletedEvent` for each user object
            $portalUser = $this->userService->getPortalUser($account);

            $userList = $this->userListBuilder
                ->fromAccount($account)
                ->withProjectRoomUser()
                ->withCommunityRoomUser()
                ->withUserRoomUser()
                ->withPrivateRoomUser()
                ->getList();
        } catch (LogicException) {
            // Account without portal user
        } finally {
            $users = iterator_to_array($userList);
            array_walk($users, fn(cs_user_item $user) => $user->delete());

            $this->entityManager->remove($account);
            $this->entityManager->flush();

            $portalUser?->delete();
        }
    }

    public function lock(Account $account): void
    {
        try {
            $portalUser = $this->userService->getPortalUser($account);
            $portalUser->reject();
            $portalUser->save();
        } catch (LogicException) {
            // Account without portal user
        }

        $account->setLocked(true);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    public function unlock(Account $account): void
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
        // This will affect the LocaleSubscriber decision
        $this->requestStack->getSession()->set('_locale', $account->getLanguage());
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
