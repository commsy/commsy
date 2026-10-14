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
use App\Entity\Todos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Todos>
 */
class TodosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todos::class);
    }

    /**
     * The tasks an export of this room carries.
     *
     * Deleted entries and drafts stay out, the draft flag being a column on
     * `items` rather than on the rubric.
     *
     * @return Todos[] oldest first, a window of them if $limit is given
     */
    public function findForExport(int $contextId, int $offset = 0, ?int $limit = null): array
    {
        return $this->exportable()
            ->andWhere('t.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->orderBy('t.itemId', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * How many entries {@see self::findForExport()} holds in all.
     */
    public function countForExport(int $contextId): int
    {
        return (int) $this->exportable()
            ->select('COUNT(t.itemId)')
            ->andWhere('t.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * One entry by id, on the same terms as {@see self::findForExport()}.
     */
    public function findOneForExport(int $itemId): ?Todos
    {
        return $this->exportable()
            ->andWhere('t.itemId = :itemId')
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function exportable(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = t.itemId')
            ->andWhere('t.deleter IS NULL')
            ->andWhere('t.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL');
    }
}
