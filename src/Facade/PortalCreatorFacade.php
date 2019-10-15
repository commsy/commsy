<?php


namespace App\Facade;


use App\Entity\AuthSource;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;

class PortalCreatorFacade
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function persistPortal(Portal $portal)
    {
        $authSource = new AuthSource();
        $authSource->setPortal($portal);
        $authSource->setTitle('Lokal');
        $authSource->setType('local');
        $authSource->setEnabled(true);
        $authSource->setDefault(true);
        $authSource->setAddAccount(true);
        $authSource->setChangeUsername(true);
        $authSource->setDeleteAccount(true);
        $authSource->setChangeUserdata(true);
        $authSource->setChangePassword(true);
        $authSource->setCreateRoom(true);

        $portal->addAuthSource($authSource);

        $this->entityManager->persist($portal);
        $this->entityManager->persist($authSource);
        $this->entityManager->flush();
    }
}