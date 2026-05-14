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

namespace App\Facade;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class AccountCreatorFacade
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function persistNewAccount(Account $account): cs_user_item
    {
        $this->assertNoOrphanProfilesForNewAccount($account);

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $userManager = $this->legacyEnvironment->getUserManager();

        /*
         * This is a real gotcha. When the legacy code persists a new user, it will only create a private room
         * if the legacy environment portal id matches the user context id. We force this behaviour by setting
         * it here explicitly.
         */
        $this->legacyEnvironment->setCurrentPortalID($account->getPortal()?->getId());

        // Create portal user. The private room item will also be created.
        // `setAccountID` must run before `save()`: the legacy save path
        // resolves the linked Account via `cs_user_item::getAccountID()`
        // to populate the row's `account_id` column on INSERT.
        $portalUser = $userManager->getNewItem();
        $portalUser->setAccountID($account->getId());
        $portalUser->setContextID($account->getPortal()?->getId());
        $portalUser->setUserID($account->getUsername());
        $portalUser->setFirstname($account->getFirstname());
        $portalUser->setLastname($account->getLastname());
        $portalUser->setEmail($account->getEmail());
        $portalUser->setLanguage($account->getLanguage()->value);
        $portalUser->makeUser();
        $portalUser->save();

        return $portalUser;
    }

    /**
     * Fail-loud sanity check guarding the signup path against the username-reuse
     * inheritance bug: refuses to create a new account when any non-soft-deleted
     * user row already exists for the same (username, auth_source) in the
     * target portal. Such a row would otherwise be silently adopted by the new
     * account on the first login (see {@see \App\Account\AccountManager::propagateAccountDataToProfiles}).
     *
     * Once the one-shot user-consistency cleanup migration has run and the
     * {@see \App\Account\AccountDeleter} orphan sweep is active, this guard
     * MUST stay at zero hits. A trigger here is therefore a drift signal — a
     * code path leaked a user row that the deletion pipeline did not catch,
     * or a concurrent write outraced the sweep. The correct response is to
     * investigate the logs and manually soft-delete the offending rows, not
     * to re-run a migration (Doctrine migrations are monotonic).
     */
    private function assertNoOrphanProfilesForNewAccount(Account $account): void
    {
        $portal = $account->getPortal();
        if ($portal === null) {
            return;
        }

        $orphans = $this->userRepository->findActiveOrphansByUsernameInPortal(
            $account->getUsername(),
            $portal->getId(),
        );

        if ($orphans === []) {
            return;
        }

        $itemIds = array_map(static fn (User $u) => $u->getItemId(), $orphans);

        $this->logger->error(
            'AccountCreatorFacade blocked: orphaned user profiles already exist for the requested username.',
            [
                'username' => $account->getUsername(),
                'portal_id' => $portal->getId(),
                'orphan_count' => count($orphans),
                'orphan_item_ids' => $itemIds,
            ]
        );

        throw new RuntimeException(sprintf(
            'Cannot create account "%s" in portal %d: %d orphaned profile(s) detected (user.item_id: %s). '
            . 'This signals a drift from the user-consistency invariant — investigate the deletion path '
            . 'that produced these rows, manually soft-delete the listed user.item_id values, then retry.',
            $account->getUsername(),
            $portal->getId(),
            count($orphans),
            implode(', ', $itemIds),
        ));
    }
}
