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

use App\Entity\Dates;
use App\Entity\Items;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dates>
 */
class DatesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dates::class);
    }

    /**
     * The dates an export of this room carries.
     *
     * Deleted entries and drafts stay out, the draft flag being a column on
     * `items`. Nothing else is filtered: the room's own list shows every date
     * regardless of `date_mode` (see cs_dates_manager::setWithoutDateModeLimit),
     * and an export should show no less and no more.
     *
     * @return Dates[] oldest first
     */
    public function findForExport(int $contextId): array
    {
        return $this->exportable()
            ->andWhere('d.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->orderBy('d.itemId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * One entry by id, on the same terms as {@see self::findForExport()}.
     */
    public function findOneForExport(int $itemId): ?Dates
    {
        return $this->exportable()
            ->andWhere('d.itemId = :itemId')
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function exportable(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = d.itemId')
            ->andWhere('d.deleter IS NULL')
            ->andWhere('d.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL');
    }
}
