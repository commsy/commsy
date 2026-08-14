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

use App\Entity\AuditLogEntry;
use App\Entity\Portal;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLogEntry>
 */
class AuditLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLogEntry::class);
    }

    /**
     * Writes one entry through the shared unit of work.
     *
     * Deliberately not isolated the way {@see LogRepository::addLog()} is: an
     * entry points at its portal and at the acting account, both managed by the
     * shared manager, and a second manager could not persist those references.
     * Committing together with the act being recorded is the better property
     * anyway — an entry that outlived a rolled back change would be a lie.
     */
    public function add(AuditLogEntry $entry): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();
    }

    /**
     * Entries of one portal, newest first.
     *
     * The alias is `e` because {@see \App\Filter\AuditLogFilterType} names it.
     */
    public function createPortalQueryBuilder(Portal $portal): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.portal = :portal')
            ->setParameter('portal', $portal)
            ->orderBy('e.occurredAt', 'DESC')
            ->addOrderBy('e.id', 'DESC');
    }

    /**
     * Drops entries recorded before the given point in time.
     *
     * @return int number of removed entries
     */
    public function deleteOlderThan(DateTimeImmutable $threshold): int
    {
        return $this->getEntityManager()
            ->createQuery('DELETE App\Entity\AuditLogEntry e WHERE e.occurredAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}
