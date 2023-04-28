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

namespace App\Services;

use App\Entity\RoomCategories;
use App\Entity\RoomCategoriesLinks;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class RoomCategoriesService
{
    private readonly EntityManagerInterface $em;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    public function getListRoomCategories($contextId)
    {
        $result = [];

        $repository = $this->em->getRepository(RoomCategories::class);
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

    public function getRoomCategory($roomCategoryId)
    {
        $repository = $this->em->getRepository(RoomCategories::class);
        $query = $repository->createQueryBuilder('room_categories')
            ->select()
            ->where('room_categories.id = :roomCategoryId')
            ->setParameter('roomCategoryId', $roomCategoryId)
            ->getQuery();

        return $roomCategories = $query->getResult();
    }

    public function getRoomCategoriesLinkedToContext($contextId)
    {
        $repository = $this->em->getRepository(RoomCategoriesLinks::class);
        $query = $repository->createQueryBuilder('room_categories_links')
            ->select()
            ->where('room_categories_links.context_id = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();

        return $roomCategories = $query->getResult();
    }

    public function setRoomCategoriesLinkedToContext($contextId, $roomCategories)
    {
        $linkedCategories = $this->getRoomCategoriesLinkedToContext($contextId);
        foreach ($linkedCategories as $linkedCategory) {
            if (!in_array($linkedCategory->getCategoryId(), $roomCategories)) {
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

    public function removeRoomCategory($roomCategory)
    {
        $repository = $this->em->getRepository(RoomCategoriesLinks::class);

        $query = $repository->createQueryBuilder('room_categories_links')
            ->select()
            ->where('room_categories_links.category_id = :category_id')
            ->setParameter('category_id', $roomCategory->getId())
            ->getQuery();
        $roomCategoriesLinks = $query->getResult();

        foreach ($roomCategoriesLinks as $roomCategoriesLink) {
            $this->em->remove($roomCategoriesLink);
        }

        $this->em->remove($roomCategory);

        $this->em->flush();
    }
}
