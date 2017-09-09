<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\RoomCategories;
use CommsyBundle\Entity\RoomCategoriesLinks;
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

        return $roomCategories = $query->getResult();
    }

    public function getRoomCategoriesLinkedToContext ($contextId) {
        $repository = $this->em->getRepository('CommsyBundle:RoomCategoriesLinks');
        $query = $repository->createQueryBuilder('room_categories_links')
            ->select()
            ->where('room_categories_links.context_id = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();

        return $roomCategories = $query->getResult();
    }

    public function setRoomCategoriesLinkedToContext ($contextId, $roomCategories) {
        $linkedCategories = $this->getRoomCategoriesLinkedToContext($contextId);
        foreach ($linkedCategories as $linkedCategory) {
            if (!in_array($linkedCategory->setCategoryId(), $roomCategories)) {
                $this->em->remove($linkedCategory);
            }
        }

        foreach ($roomCategories as $roomCategory) {
            $foundCategory = false;
            foreach ($linkedCategories as $linkedCategory) {
                if ($linkedCategory->getId() == $roomCategory) {
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                $roomCategoryLink = new RoomCategoriesLinks();
                $roomCategoryLink->setContextId($contextId);
                $roomCategoryLink->setCategoryId($roomCategory);
                $this->em->persist($roomCategoryLink);
            }
        }

        $this->em->flush();
    }
}