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
use App\Utils\ItemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixAbandonedGrouprooms implements DatabaseCheck
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ItemService $itemService,
        LegacyEnvironment $legacyEnvironment
    ) {
    }

    public function getPriority()
    {
        return 200;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $io->text('Inspecting group rooms, looking for missing project room relations');

        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Room::class, 'r')
            ->where('r.deleter IS NULL')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.type = :roomType')
            ->setParameter('roomType', 'grouproom')
            ->getQuery();

        /** @var Room[] $groupRooms */
        $groupRooms = $qb->execute();

        $progressBar = new ProgressBar($io, count($groupRooms));
        $progressBar->start();

        foreach ($groupRooms as $groupRoom) {
            if ($io->isVerbose()) {
                $io->text('Processing room '.$groupRoom->getTitle().'('.$groupRoom->getItemId().')');
            }

            $extras = $groupRoom->getExtras();
            $found = false;
            if (isset($extras['PROJECT_ROOM_ITEM_ID'])) {
                $projectRoomId = $extras['PROJECT_ROOM_ITEM_ID'];

                $projectRoom = $this->itemService->getTypedItem($projectRoomId);
                if (null !== $projectRoom) {
                    $found = true;
                }
            }

            if (false === $found) {
                $io->warning('"PROJECT_ROOM_ITEM_ID" not set or no related room found for grouproom with id '.$groupRoom->getItemId());

                $this->entityManager->createQueryBuilder()
                    ->delete(Room::class, 'r')
                    ->where('r.itemId = :roomId')
                    ->setParameter('roomId', $groupRoom->getItemId())
                    ->getQuery()
                    ->execute();
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return true;
    }
}
