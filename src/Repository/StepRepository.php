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

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Items;
use App\Entity\Step;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the steps a task is broken into.
 *
 * @extends ServiceEntityRepository<Step>
 */
class StepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Step::class);
    }

    /**
     * A sub-entry can be a draft of its own while its parent is not, so the
     * draft flag is asked here as well as on the parent.
     *
     * @return Step[] in the order they were written
     */
    public function findForExport(int $todoItemId): array
    {
        return $this->createQueryBuilder('s')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = s.itemId')
            ->andWhere('s.todoItemId = :todoItemId')
            ->andWhere('s.deleter IS NULL')
            ->andWhere('s.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL')
            ->setParameter('todoItemId', $todoItemId)
            ->orderBy('s.itemId', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
