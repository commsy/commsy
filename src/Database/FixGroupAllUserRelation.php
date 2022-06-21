<?php

namespace App\Database;


use App\Entity\Portal;
use App\Entity\Room;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_group_item;
use cs_link_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixGroupAllUserRelation implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(
        EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->entityManager = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 199;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        // find all active portals
        $qb = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from('App:Portal', 'p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery();
        $portals = $qb->execute();

        $groupManager = $this->legacyEnvironment->getGroupManager();
        $userManager = $this->legacyEnvironment->getUserManager();

        /** @var Portal[] $portals */
        foreach ($portals as $portal) {
            $io->text('Inspecting relations between users and system group "ALL" in portal ' . $portal->getTitle() . '(' . $portal->getId() . ')');

            $qb = $this->entityManager->createQueryBuilder()
                ->select('r')
                ->from('App:Room', 'r')
                ->where('r.deleter IS NULL')
                ->andWhere('r.deletionDate IS NULL')
                ->andWhere('r.contextId = :contextId')
                ->andWhere('r.type = :roomType')
                ->setParameter('contextId', $portal->getId())
                ->setParameter('roomType', 'project')
                ->getQuery();

            /** @var Room[] $projectRooms */
            $projectRooms = $qb->execute();

            $progressBar = new ProgressBar($io, count($projectRooms));
            $progressBar->start();

            foreach ($projectRooms as $projectRoom) {
                if ($io->isVerbose()) {
                    $io->text('Processing room "' . $projectRoom->getTitle() . '" - ' . $projectRoom->getItemId());
                }

                // get group "ALL"
                $groupManager->reset();
                $groupManager->setContextLimit($projectRoom->getItemId());

                /** @var cs_group_item $groupAll */
                $groupAll = $groupManager->getItemByName('ALL');
                $groupAllMembers = $groupAll->getMemberItemList();

                // get list of users
                $userManager->reset();
                $userManager->setContextLimit($projectRoom->getItemId());
                $userManager->setUserLimit();
                $userManager->select();
                $userList = $userManager->get();

                if ($userList && $userList->isNotEmpty()) {
                    // iterate users
                    /** @var \cs_user_item $userItem */
                    $userItem = $userList->getFirst();
                    while ($userItem) {
                        if (!$userItem->isRoot()) {
                            if (!$groupAllMembers->inList($userItem)) {
                                $io->warning('Missing user relation found');

                                $linkManager = $this->legacyEnvironment->getLinkItemManager();

                                /** @var cs_link_item $linkItem */
                                $linkItem = $linkManager->getNewItem();

                                $linkItem->setCreatorItem($userItem);
                                $linkItem->setModificatorItem($userItem);
                                $linkItem->setFirstLinkedItem($groupAll);
                                $linkItem->setSecondLinkedItem($userItem);

                                $linkItem->save();
                            }
                        }

                        $userItem = $userList->getNext();
                    }
                }

                $progressBar->advance();
            }

            $progressBar->finish();
        }

        return true;
    }
}