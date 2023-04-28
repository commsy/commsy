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
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;

class AccountCreatorFacade
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function persistNewAccount(Account $account): cs_user_item
    {
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $userManager = $this->legacyEnvironment->getUserManager();

        /*
         * This is a real gotcha. When the legacy code persists a new user, it will only create a private room
         * if the legacy environment portal id matches the user context id. We force this behaviour by setting
         * it here explicitly.
         */
        $this->legacyEnvironment->setCurrentPortalID($account->getContextId());

        // Create portal user
        // The private room item will also be created
        $portalUser = $userManager->getNewItem();
        $portalUser->setAuthSource($account->getAuthSource()->getId());
        $portalUser->setContextID($account->getContextId());
        $portalUser->setUserID($account->getUsername());
        $portalUser->setFirstname($account->getFirstname());
        $portalUser->setLastname($account->getLastname());
        $portalUser->setEmail($account->getEmail());
        $portalUser->setLanguage($account->getLanguage());
        $portalUser->makeUser();
        $portalUser->save();

        return $portalUser;
    }
}
