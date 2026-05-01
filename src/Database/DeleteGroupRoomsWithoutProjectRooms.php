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
use App\Room\GroupRoomDeleter;
use App\Room\RoomDeletionOptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteGroupRoomsWithoutProjectRooms extends GeneralCheck
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private readonly GroupRoomDeleter $groupRoomDeleter
    ) {
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

                // DB-fix path: no user session, no moderator, no UI —
                // `forDbFix()` is implicitly silent so no
                // WorkspaceDeletedEvent mails fan out. Deleter id 0
                // mirrors legacy behaviour when `cs_user_item::delete()`
                // ran without a current-user context.
                $this->groupRoomDeleter->softDeleteRoom(
                    (int) $groupRoom->getItemId(),
                    0,
                    RoomDeletionOptions::forDbFix()
                );

                $io->caution("Group room '{$groupRoom->getItemId()}' was deleted");
            }
        }

        return true;
    }
}
