<?php


namespace App\Facade;


use App\Entity\Account;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;

class AccountCreatorFacade
{
    private $entityManager;

    private $legacyEnvironment;

    public function __construct(EntityManagerInterface $entityManager, LegacyEnvironment $legacyEnvironment)
    {
        $this->entityManager = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function persistNewAccount(Account $account)
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
    }
}