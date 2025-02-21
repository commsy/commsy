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

class FixGroupRoomsWithoutGroups extends GeneralCheck
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
        $labelRepository = $this->entityManager->getRepository(Labels::class);

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

            $groupId = $groupRoom->getExtras()['GROUP_ITEM_ID'] ?? null;

            $group = $labelRepository->findOneBy(['itemId' => $groupId]);
            if (!$group) {
                $io->warning("Group with id '{$groupId}' not found for grouproom {$groupRoom->getItemId()}");

                $projectId = $groupRoom->getExtras()['PROJECT_ROOM_ITEM_ID'] ?? null;
                if (!$projectId) {
                    continue;
                }

                $groupManager = $this->legacyEnvironment->getGroupManager();
                $group = $groupManager->getNewItem();

                $groupRoomManager = $this->legacyEnvironment->getGroupRoomManager();

                /** @var cs_grouproom_item $legacyGroupRoom */
                $legacyGroupRoom = $groupRoomManager->getItem($groupRoom->getItemId());

                $groupRoomCreator = $legacyGroupRoom->getCreator();
                $groupRoomModifier = $legacyGroupRoom->getModificatorItem();

                $group->setTitle($groupRoom->getTitle());
                $group->setContextID($projectId);
                $group->setGroupRoomItemID($groupRoom->getItemId());
                $group->setCreatorItem($groupRoomCreator?->getRelatedUserItemInContext($projectId));
                $group->setModificatorItem($groupRoomModifier?->getRelatedUserItemInContext($projectId));
                $group->setChangeModificationOnSave(false);
                $group->save(false);

                $legacyGroupRoom->setLinkedProjectRoomItemID($projectId);
                $legacyGroupRoom->setLinkedGroupItemID($group->getItemID());
                $legacyGroupRoom->setChangeModificationOnSave(false);
                $legacyGroupRoom->saveWithoutChangingModificationInformation();

                $userManager = $this->legacyEnvironment->getUserManager();
                $userManager->resetLimits();
                $userManager->setContextLimit($groupRoom->getItemId());
                $userManager->select();
                $groupRoomMembers = $userManager->get();
                foreach ($groupRoomMembers as $groupRoomMember) {
                    /** @var cs_user_item $groupRoomMember */
                    $userInProject = $groupRoomMember->getRelatedUserItemInContext($projectId);
                    if ($userInProject && !$group->isMember($userInProject)) {
                        $group->addMember($userInProject);
                    }
                }

                $io->info('Group created');
            }
        }

        return true;
    }
}
