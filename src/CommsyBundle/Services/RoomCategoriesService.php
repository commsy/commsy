<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\Calendars;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Sabre\VObject;

class RoomCategoriesService
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

    public function getListRoomCategories ($contextId) {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:RoomCategories');
        $query = $repository->createQueryBuilder('room_categories')
            ->select()
            ->where('room_categories.context_id = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();
        $roomCategories = $query->getResult();

        foreach ($roomCategories as $roomCategory) {
            $result[] = $roomCategory;
        }

        return $result;
    }

    public function getRoomCategory ($roomCategoryId) {
        $repository = $this->em->getRepository('CommsyBundle:RoomCategories');
        $query = $repository->createQueryBuilder('room_categories')
            ->select()
            ->where('room_categories.id = :roomCategoryId')
            ->setParameter('roomCategoryId', $roomCategoryId)
            ->getQuery();

        return $calendars = $query->getResult();
    }

    public function getRoomCategoriesLinkedToContext ($contextId) {
        $repository = $this->em->getRepository('CommsyBundle:RoomCategoriesLinks');
        $query = $repository->createQueryBuilder('room_categories_links')
            ->select()
            ->where('room_categories_links.context_id = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();

        return $calendars = $query->getResult();
    }
}