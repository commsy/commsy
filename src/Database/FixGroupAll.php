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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixGroupAll implements DatabaseCheck
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        private EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 200;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $groupManager = $this->legacyEnvironment->getGroupManager();

        $io->text('Inspecting project rooms, looking for system group "ALL"');

        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Room::class, 'r')
            ->where('r.deleter IS NULL')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.type = :roomType')
            ->setParameter('roomType', 'project')
            ->getQuery();

        /** @var Room[] $projectRooms */
        $projectRooms = $qb->execute();

        $progressBar = new ProgressBar($io, count($projectRooms));
        $progressBar->start();

        foreach ($projectRooms as $projectRoom) {
            if ($io->isVerbose()) {
                $io->text('Processing room '.$projectRoom->getTitle().'('.$projectRoom->getItemId().')');
            }

            // get group "ALL"
            $groupManager->reset();
            $groupManager->setContextLimit($projectRoom->getItemId());
            $groupAll = $groupManager->getItemByName('ALL');

            if (!$groupAll) {
                $io->warning('Missing group found');

                /** @var \cs_group_item $group */
                $group = $groupManager->getNewItem('group');
                $group->setName('ALL');
                $group->setDescription('GROUP_ALL_DESC');
                $group->setContextID($projectRoom->getItemId());
                $group->setCreatorID($projectRoom->getCreator()->getItemId());
                $group->makeSystemLabel();
                $group->save();
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return true;
    }
}
