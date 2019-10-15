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