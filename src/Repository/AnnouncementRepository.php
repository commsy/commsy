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

namespace App\Repository;

use App\Entity\Announcement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * Announcements whose validity ends within the given window, deleted ones
     * left out. Used to warn their authors before an announcement drops out of
     * the room unnoticed.
     *
     * @return Announcement[] soonest expiry first
     */
    public function findExpiringBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.enddate > :from')->setParameter('from', $from)
            ->andWhere('a.enddate <= :to')->setParameter('to', $to)
            ->andWhere('a.deleter IS NULL')
            ->andWhere('a.deletionDate IS NULL')
            ->orderBy('a.enddate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
