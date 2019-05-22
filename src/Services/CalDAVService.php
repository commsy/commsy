<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class CalDAVService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    private $serviceContainer;

    public function __construct(EntityManagerInterface $entityManager, Container $container)
    {
        $this->em = $entityManager;
        $this->serviceContainer = $container;
    }

    public function setCalDAVHash ($userId, $password, $realm) {
        $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();

        $repository = $this->em->getRepository('App:User');
        $query = $repository->createQueryBuilder('user')
            ->select()
            ->where('user.contextId = :portalId AND user.userId = :userId')
            ->setParameter('portalId', $legacyEnvironment->getCurrentPortalId())
            ->setParameter('userId', $userId)
            ->getQuery();

        $users = $query->getResult();

        if (isset($users[0])) {
            $userService = $this->serviceContainer->get("commsy_legacy.user_service");
            $legacyUserItem = $userService->getUser($users[0]->getItemId());
            $privateRoomUser = $legacyUserItem->getRelatedPrivateRoomUserItem();

            // TODO: User item might be null in some cases when using multiple auth sources
            if ($privateRoomUser) {
                $hash = md5($userId . ':' . $realm . ':' . $password);

                $this->em->createQuery('UPDATE App:Hash hash SET hash.caldav = :hash WHERE hash.userItemId = :itemId')
                    ->setParameter('hash', $hash)
                    ->setParameter('itemId', $privateRoomUser->getItemId())
                    ->getResult();
            }
        }
    }
}