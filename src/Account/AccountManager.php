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
use App\Entity\Portal;
use App\Repository\AccountsRepository;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use BadMethodCallException;
use cs_environment;
use cs_user_item;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class AccountManager
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        private AccountsRepository $accountsRepository,
        private UserService $userService,
        private RequestStack $requestStack,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function propagateUsernameChange(Account $account, string $username): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->changeUserID($username, $account);

        $account->setUsername($username);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    public function propagateAccountDataToProfiles(Account $account, bool $updateUsername = false, ?Account $lookupAccount = null): void
    {
        /*
         * This is a real gotcha. When the legacy code persists a new user, it will only create a private room
         * if the legacy environment portal id matches the user context id. We force this behaviour by setting
         * it here explicitly.
         */
        $this->legacyEnvironment->setCurrentPortalID($account->getPortal()->getId());

        $portalUser = $this->userService->getPortalUser($lookupAccount ?? $account);
        if ($portalUser) {
            $relatedUsers = $portalUser->getRelatedUserList(true, true);
            $relatedUsers->add($portalUser);

            /*
             * TODO: This is still very slow when changes occur, but will drastically improve login performance in
             * most of the "normal" cases
             */
            foreach ($relatedUsers as $relatedUser) {
                /** @var cs_user_item $relatedUser */

                // getRelatedUserList joins on account_id, so every returned
                // row must belong to $account. A mismatch indicates the
                // lookup contract was violated; abort before any write.
                if ($relatedUser->getAccountID() !== $account->getId()) {
                    throw new \LogicException(sprintf(
                        'propagateAccountDataToProfiles received user.item_id=%d with account_id=%s for account %d — getRelatedUserList must only return rows of the same account.',
                        $relatedUser->getItemID(),
                        $relatedUser->getAccountID() === null ? 'NULL' : (string) $relatedUser->getAccountID(),
                        $account->getId(),
                    ));
                }

                if ($relatedUser->getFirstname() !== $account->getFirstname() ||
                    $relatedUser->getLastname() !== $account->getLastname() ||
                    $relatedUser->getEmail() !== $account->getEmail()
                ) {
                    $relatedUser->setFirstname($account->getFirstname());
                    $relatedUser->setLastname($account->getLastname());
                    $relatedUser->setEmail($account->getEmail());

                    $relatedUser->save();
                }

                if ($updateUsername && $relatedUser->getUserID() !== $account->getUsername()) {
                    $relatedUser->setUserID($account->getUsername());
                    $relatedUser->save();
                }
            }
        }
    }

    public function getAccount(cs_user_item $user, int $portalId): ?Account
    {
        return $this->getAccountForUser($user);
    }

    public function getAccountForUser(cs_user_item $user): ?Account
    {
        $accountId = $user->getAccountID();
        if ($accountId === null) {
            return null;
        }

        return $this->entityManager->getRepository(Account::class)->find($accountId);
    }

    public function getAccounts(int $portalId, cs_user_item ...$users): iterable
    {
        foreach ($users as $user) {
            yield $this->getAccount($user, $portalId);
        }
    }

    public function getPortal(Account $account): ?Portal
    {
        return $account->getPortal();
    }

    /**
     * @deprecated Use AccountDeleter::dispatch() instead
     */
    public function delete(Account $account): void
    {
        throw new BadMethodCallException('Use AccountDeleter::dispatch() for account deletion.');
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

    public function updateUserLocale(Account $account, AccountLanguage $language): void
    {
        $account->setLanguage($language);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // Update the user's session here too (normally done on login)
        // This will affect the LocaleSubscriber decision
        $this->requestStack->getSession()->set('_locale', $account->getLanguage()->value);
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

    /**
     * Revokes the pending notifications of one portal: a warned account goes back
     * to the state before the warning.
     *
     * The timestamp belongs to the state it describes — it is the base for
     * that state's deadline. `active` has no deadline (the next step is
     * decided by the last login), so it carries null. `idle` has one, so it
     * gets a fresh one: the deletion notice is being revoked, and the old
     * value would carry a deadline nobody announced any more.
     */
    public function resetInactivityToPreviousNonNotificationState(Portal $portal): void
    {
        $this->accountsRepository->updateActivity($portal, Account::ACTIVITY_IDLE_NOTIFIED, Account::ACTIVITY_IDLE, new DateTime());
        $this->accountsRepository->updateActivity($portal, Account::ACTIVITY_ACTIVE_NOTIFIED, Account::ACTIVITY_ACTIVE, null);
    }
}
