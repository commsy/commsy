<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 22.02.18
 * Time: 22:18
 */

namespace App\Database;


use App\Services\LegacyEnvironment;
use App\Database\Resolve\CreateGroupAllResolution;
use App\Entity\Room;
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

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 200;
    }

    public function findProblems(SymfonyStyle $io, int $limit)
    {
        $groupManager = $this->legacyEnvironment->getGroupManager();

        $io->text('Inspecting project rooms, looking for system group "ALL"');

        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from('App:Room', 'r')
            ->where('r.deleter IS NULL')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.type = :roomType')
            ->setParameter('roomType', 'project')
            ->getQuery();

        /** @var Room[] $projectRooms */
        $projectRooms = $qb->execute();

        $problems = [];

        foreach ($projectRooms as $projectRoom) {
            if ($io->isVerbose()) {
                $io->text('Processing room ' . $projectRoom->getTitle() . '(' . $projectRoom->getItemId() . ')');
            }

            // get group "ALL"
            $groupManager->reset();
            $groupManager->setContextLimit($projectRoom->getItemId());
            $groupAll = $groupManager->getItemByName('ALL');

            if (!$groupAll) {
                $io->warning('Missing group found');

                $problems[] = new DatabaseProblem($projectRoom);

                if ($limit > 0 && sizeof($problems) === $limit) {
                    $io->warning('Number of problems found reached limit -> early return. Please rerun the command.');
                    return $problems;
                }
            }
        }

        return $problems;
    }

    public function getResolutionStrategies()
    {
        return [
            new CreateGroupAllResolution($this->legacyEnvironment),
        ];
    }
}