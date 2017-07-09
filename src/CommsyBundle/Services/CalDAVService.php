<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\Invitations;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class CalDAVService
{

    /**
     * @var EntityManager $em
     */
    private $em;

    private $serviceContainer;

    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->em = $entityManager;
        $this->serviceContainer = $container;
    }

    public function setCalDAVHash ($userId, $password, $realm) {
        $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();
        #$legacyEnvironment->setCurrentContextId($portalId);
        #$legacyEnvironment->setCurrentPortalId($portalId);

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setPortalIDLimit($legacyEnvironment>-getCurrentPortalId());
        $userManager->setUserIDLimit($userId);
        $userManager->select();
        $userList = $userManager->get();
        $userItem = $userList->getFirst();
        #$legacyEnvironment->setCurrentUser($userItem);

        if ($userItem) {
            $portalUser = $userItem->getRelatedPrivateRoomUserItem();

            $repository = $this->em->getRepository('CommsyBundle:Hash');
            $repository->createQueryBuilder('hash')
                ->update('hash', 'h')
                ->set('h.caldav', md5($userId . ':' . $realm . ':' . $password))
                ->where('h.user_item_id = :user_item_id')
                ->setParameter('user_item_id', $portalUser->getItemId())
                ->getQuery()
                ->execute();
        }
    }
}