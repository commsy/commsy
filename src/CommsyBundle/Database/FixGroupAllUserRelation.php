<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 09.02.18
 * Time: 16:23
 */

namespace CommsyBundle\Database;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

//        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('commsy:db:fix-user-group-all')
            ->setDescription('Ensures every user is present in the system group "ALL"')
        ;
    }

    public function check()
    {
        // TODO: Implement check() method.
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        foreach ($portals as $portal) {
            $output->writeln('<info>inspecting relations between users and system group "ALL" in portal ' . $portal->getTitle() . '(' . $portal->getItemId() . ')</info>');

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

            $projectRooms = $qb->execute();

            foreach ($projectRooms as $projectRoom) {
                $output->writeln('<info>processing room ' . $projectRoom->getTitle() . '(' . $projectRoom->getItemId() . ')</info>');

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
                    $numUnrelated = 0;

                    // iterate users
                    /** @var \cs_user_item $userItem */
                    $userItem = $userList->getFirst();
                    while ($userItem) {
                        if (!$userItem->isRoot()) {
                            if (!$userItem->isInGroup($groupAll)) {
                                $userItem->setGroup($groupAll);
                                $userItem->setChangeModificationOnSave(false);
                                $userItem->save();

                                $numUnrelated++;
                            }
                        }

                        $userItem = $userList->getNext();
                    }

                    $output->writeln('<info>' . $numUnrelated . ' relations added</info>');
                }
            }
        }
    }
}