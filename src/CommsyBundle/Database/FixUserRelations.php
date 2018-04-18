<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.04.18
 * Time: 18:19
 */

namespace CommsyBundle\Database;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixUserRelations implements DatabaseCheck
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
        return 300;
    }

    public function check(SymfonyStyle $io)
    {
        $io->text('Inspecting tables with user relations');

        $genericUserRelatedTables = [
            'annotations',
            'announcement',
            'assessments',
            'dates',
            'discussionarticles',
            'discussions',
            'files',
            'labels',
            'link_items',
            'materials',
            'portal',
            'portfolio',
            'room',
            'room_privat',
            'section',
            'server',
            'step',
            'tag',
            'tasks',
            'todos',
            'user'
        ];

        foreach ($genericUserRelatedTables as $genericUserRelatedTable) {
            $io->text('Inspecting table "' . $genericUserRelatedTable . '"');

            $qb = $this->em->getConnection()->createQueryBuilder()
                ->select('t.creator_id'/*, 't.modifier_id'*/)
                ->from($genericUserRelatedTable, 't')
                ->leftJoin('t', 'user', 'c', 't.creator_id = c.item_id')
                ->where('t.deleter_id IS NULL')
                ->andWhere('t.deletion_date IS NULL')
                ->andWhere('c.item_id IS NULL');

            $missingRelations = $qb->execute();

            if ($missingRelations->rowCount() > 0) {
                $io->warning('Missing user relations found');

                $this->fixes[] = [
                    $genericUserRelatedTable,
                ];
            }
        }

        return sizeof($this->fixes) === 0;
    }

    public function resolve(SymfonyStyle $io)
    {

    }
}