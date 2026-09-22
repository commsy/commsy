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
use App\Entity\Discussions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Discussions>
 */
class DiscussionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussions::class);
    }

    /**
     * The discussions an export of this room carries.
     *
     * Deleted entries and drafts stay out, the draft flag being a column on
     * `items` rather than on the rubric.
     *
     * @return Discussions[] oldest first
     */
    public function findForExport(int $contextId): array
    {
        return $this->createQueryBuilder('d')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = d.itemId')
            ->andWhere('d.contextId = :contextId')
            ->andWhere('d.deleter IS NULL')
            ->andWhere('d.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL')
            ->setParameter('contextId', $contextId)
            ->orderBy('d.itemId', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
