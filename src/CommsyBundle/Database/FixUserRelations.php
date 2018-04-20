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
        return 201;
    }

    public function check(SymfonyStyle $io)
    {
        $io->text('Inspecting tables with user relations');

        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $tables = $schemaManager->listTables();

        foreach ($tables as $table) {
            if (substr($table->getName(), 0, 4) === 'zzz_') {
                continue;
            }

            $io->text('Inspecting table "' . $table->getName() . '"');

            foreach ($table->getColumns() as $column) {
                if (!in_array($column->getName(), ['creator_id', 'modifier_id'])) {
                    continue;
                }

                $io->text('Inspecting column "' . $table->getName() . '" - "' . $column->getName() . '"');

                $qb = $this->em->getConnection()->createQueryBuilder()
                    ->select('t.' . $column->getName() . ' AS missingId')
                    ->from($table->getName(), 't')
                    ->leftJoin('t', 'user', 'c', 't.' . $column->getName() . ' = c.item_id')
                    ->where('t.' . $column->getName() . ' IS NOT NULL')
                    ->andWhere('c.item_id IS NULL');

                if ($table->hasColumn('deletion_date')) {
                    $qb->andWhere('t.deletion_date IS NULL');
                }

                $missingRelations = $qb->execute();

                if ($missingRelations->rowCount() > 0) {
                    foreach ($missingRelations as $missingRelation) {
                        $io->warning('Missing user relations found - "' . $table->getName() . '" - "' . $column->getName() . '" - user with id "' . $missingRelation['missingId'] . '" not present');
                    }

                    $this->fixes[] = [
                        $table->getName(),
                    ];
                }
            }
        }

        return sizeof($this->fixes) === 0;
    }

    public function resolve(SymfonyStyle $io)
    {

    }
}