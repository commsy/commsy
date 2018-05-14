<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 09.02.18
 * Time: 16:23
 */

namespace CommsyBundle\Database;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Database\Resolve\AddMemberToGroupResolution;
use CommsyBundle\Entity\Portal;
use CommsyBundle\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixGroupAllUserRelation implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 100;
    }

    public function findProblems(SymfonyStyle $io)
    {
        // find all active portals
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from('CommsyBundle:Portal', 'p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery();
        $portals = $qb->execute();

        $groupManager = $this->legacyEnvironment->getGroupManager();
        $userManager = $this->legacyEnvironment->getUserManager();

        $problems = [];

        /** @var Portal[] $portals */
        foreach ($portals as $portal) {
            $io->text('Inspecting relations between users and system group "ALL" in portal ' . $portal->getTitle() . '(' . $portal->getItemId() . ')');

            $qb = $this->em->createQueryBuilder()
                ->select('r')
                ->from('CommsyBundle:Room', 'r')
                ->where('r.deleter IS NULL')
                ->andWhere('r.deletionDate IS NULL')
                ->andWhere('r.contextId = :contextId')
                ->andWhere('r.type = :roomType')
                ->setParameter('contextId', $portal->getItemId())
                ->setParameter('roomType', 'project')
                ->getQuery();

            /** @var Room[] $projectRooms */
            $projectRooms = $qb->execute();

            foreach ($projectRooms as $projectRoom) {
                if ($io->isVerbose()) {
                    $io->text('Processing room "' . $projectRoom->getTitle() . '" - ' . $projectRoom->getItemId());
                }

                // get group "ALL"
                $groupManager->reset();
                $groupManager->setContextLimit($projectRoom->getItemId());
                $groupAll = $groupManager->getItemByName('ALL');

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
                            if (!$userItem->isInGroup($groupAll)) {
                                $io->warning('Missing user relation found');

                                $problems[] = new DatabaseProblem([
                                    'user' => $userItem,
                                    'group' => $groupAll,
                                ]);
                            }
                        }

                        $userItem = $userList->getNext();
                    }
                }
            }
        }

        return $problems;
    }

    public function getResolutionStrategies()
    {
        return [
            new AddMemberToGroupResolution($this->legacyEnvironment),
        ];
    }
}