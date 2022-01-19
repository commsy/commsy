<?php

namespace App\Database;

use App\Entity\Room;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixAbandonedGrouprooms implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    private ItemService $itemService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ItemService $itemService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->entityManager = $entityManager;
        $this->itemService = $itemService;
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
            ->from('App:Room', 'r')
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
                $io->text('Processing room ' . $groupRoom->getTitle() . '(' . $groupRoom->getItemId() . ')');
            }

            $extras = $groupRoom->getExtras();
            $found = false;
            if (isset($extras['PROJECT_ROOM_ITEM_ID'])) {
                $projectRoomId = $extras['PROJECT_ROOM_ITEM_ID'];

                $projectRoom = $this->itemService->getTypedItem($projectRoomId);
                if ($projectRoom !== null) {
                    $found = true;
                }
            }

            if ($found === false) {
                $io->warning('"PROJECT_ROOM_ITEM_ID" not set or no related room found for grouproom with id ' . $groupRoom->getItemId());

                $this->entityManager->createQueryBuilder()
                    ->delete('App:Room', 'r')
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