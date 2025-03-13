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

namespace App\Database;

use App\Entity\Room;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_grouproom_item;
use cs_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteGroupRoomsWithoutTitle extends GeneralCheck
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        parent::__construct($entityManager);
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Room::class, 'r')
            ->where('r.type = :type')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.deleter IS NULL')
            ->andWhere('r.title = :title')
            ->setParameter('type', 'grouproom')
            ->setParameter('title', '')
            ->getQuery()
        ;

        $groupRooms = $qb->execute();
        foreach ($groupRooms as $groupRoom) {
            /** @var Room $groupRoom */
            if ($io->isVerbose()) {
                $io->text("Checking grouproom {$groupRoom->getItemId()} - '{$groupRoom->getTitle()}'");
            }

            // Check if there is only one member
            $groupRoomManager = $this->legacyEnvironment->getGroupRoomManager();
            /** @var cs_grouproom_item $legacyGroupRoom */
            $legacyGroupRoom = $groupRoomManager->getItem($groupRoom->getItemId());
            if ($legacyGroupRoom->getUserList()->getCount() > 1) {
                $io->warning("Skipping group room with id '{$groupRoom->getItemId()}', because it has more than one user");
                continue;
            }

            $itemManager = $this->legacyEnvironment->getItemManager();
            $itemManager->setContextLimit($groupRoom->getItemId());
            $itemManager->select();
            $items = $itemManager->get();

            // filter items
            $filteredItems = array_filter($items->to_array(), fn (cs_item $item) =>
                !in_array($item->getItemType(), ['user', 'tag'])
            );

            // Check if there are real items
            if (!empty($filteredItems)) {
                $io->warning("Skipping group room with id '{$groupRoom->getItemId()}', because it contains items");
                continue;
            }

            $io->warning("Invalid group room found");

            $legacyGroupRoom->delete(true);
            $io->caution("Group room '{$groupRoom->getItemId()}' was deleted");
        }

        return true;
    }
}
