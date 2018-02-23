<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 22.02.18
 * Time: 22:18
 */

namespace CommsyBundle\Database;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixGroupAll implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $fixes = [];

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 200;
    }

    public function check(SymfonyStyle $io)
    {
        $groupManager = $this->legacyEnvironment->getGroupManager();

        $io->text('Inspecting project rooms, looking for system group "ALL"');

        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from('CommsyBundle:Room', 'r')
            ->where('r.deleter IS NULL')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.type = :roomType')
            ->setParameter('roomType', 'project')
            ->getQuery();

        /** @var Room[] $projectRooms */
        $projectRooms = $qb->execute();

        foreach ($projectRooms as $projectRoom) {
            $io->text('Processing room ' . $projectRoom->getTitle() . '(' . $projectRoom->getItemId() . ')');

            // get group "ALL"
            $groupManager->reset();
            $groupManager->setContextLimit($projectRoom->getItemId());
            $groupAll = $groupManager->getItemByName('ALL');

            if (!$groupAll) {
                $io->warning('Missing group found');

                $this->fixes[] = [
                    "room" => $projectRoom,
                ];
            }
        }

        return sizeof($this->fixes) === 0;
    }

    public function resolve(SymfonyStyle $io)
    {
        $numMissing = 0;
        $groupManager = $this->legacyEnvironment->getGroupManager();

        foreach ($this->fixes as $fix) {
            /** @var Room $room */
            $room = $fix['room'];

            /** @var \cs_group_item $group */
            $group = $groupManager->getNewItem('group');
            $group->setName('ALL');
            $group->setDescription('GROUP_ALL_DESC');
            $group->setContextID($room->getItemId());
            $group->setCreatorID($room->getCreator()->getItemId());
            $group->makeSystemLabel();
            $group->save();

            $numMissing++;
        }

        $io->text($numMissing . ' groups added');

        return true;
    }
}