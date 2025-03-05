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

use App\Entity\Labels;
use App\Entity\Room;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_grouproom_item;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteGroupRoomsWithoutProjectRooms extends GeneralCheck
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
        $roomRepository = $this->entityManager->getRepository(Room::class);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Room::class, 'r')
            ->where('r.type = :type')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.deleter IS NULL')
            ->setParameter('type', 'grouproom')
            ->getQuery()
        ;

        $groupRooms = $qb->execute();
        foreach ($groupRooms as $groupRoom) {
            /** @var Room $groupRoom */
            if ($io->isVerbose()) {
                $io->text("Checking grouproom {$groupRoom->getItemId()} - '{$groupRoom->getTitle()}'");
            }

            $projectId = $groupRoom->getExtras()['PROJECT_ROOM_ITEM_ID'] ?? null;
            if (!$projectId || !$roomRepository->findOneBy(['itemId' => $projectId])) {
                $io->warning("No project room with id '{$projectId}' found for grouproom {$groupRoom->getItemId()}");

                $groupRoomManager = $this->legacyEnvironment->getGroupRoomManager();
                $legacyGroupRoom = $groupRoomManager->getItem($groupRoom->getItemId());
                $legacyGroupRoom->delete(true);

                $io->caution("Group room '{$groupRoom->getItemId()}' was deleted");
            }
        }

        return true;
    }
}
