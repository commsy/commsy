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

use App\Entity\Annotations;
use App\Entity\Items;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the comments written on an entry.
 *
 * @extends ServiceEntityRepository<Annotations>
 */
class AnnotationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Annotations::class);
    }

    /**
     * A comment records the version it was written on, but nothing reads it:
     * legacy asks for the entry alone (see cs_item::getAnnotationList()), and
     * the column is zero on all but a handful of rows. Asking for a version
     * here would drop nearly every comment a material has.
     *
     * A comment can be a draft of its own while the entry it belongs to is not.
     *
     * @return Annotations[] oldest first
     */
    public function findForEntry(int $linkedItemId): array
    {
        return $this->createQueryBuilder('a')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = a.itemId')
            ->andWhere('a.linkedItemId = :linkedItemId')
            ->andWhere('a.deleter IS NULL')
            ->andWhere('a.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL')
            ->setParameter('linkedItemId', $linkedItemId)
            ->orderBy('a.itemId', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
