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

use App\Entity\Hash;
use App\Hash\ICalHashGenerator;
use App\Hash\RssHashGenerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class HashRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry,
        private RssHashGenerator $rssHashGenerator,
        private ICalHashGenerator $iCalHashGenerator
    ) {
        parent::__construct($registry, Hash::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findByUserId(int $userId): Hash
    {
        $query = $this->getEntityManager()
            ->createQuery("
                SELECT h FROM App\Entity\Hash h where h.userId = :userId
            ")
            ->setParameter('userId', $userId);

        return $query->getSingleResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findByRssHash(string $rssHash): Hash
    {
        $query = $this->getEntityManager()
            ->createQuery("
                SELECT h FROM App\Entity\Hash h where h.rss = :rss
            ")
            ->setParameter('rss', $rssHash);

        return $query->getSingleResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findByICalHash(string $icalHash): Hash
    {
        $query = $this->getEntityManager()
            ->createQuery("
                SELECT h FROM App\Entity\Hash h where h.ical = :ical
            ")
            ->setParameter('ical', $icalHash);

        return $query->getSingleResult();
    }

    public function createHash(int $userId): void
    {
        $hash = new Hash();
        $hash->setUserId($userId);
        $hash->setRss($this->rssHashGenerator->generate($userId));
        $hash->setIcal($this->iCalHashGenerator->generate($userId));

        $em = $this->getEntityManager();
        $em->persist($hash);
        $em->flush();
    }

    public function deleteHash(Hash $hash): void
    {
        $em = $this->getEntityManager();
        $em->remove($hash);
        $em->flush();
    }

    public function deleteHashesByUserIds(array $userIds): void
    {
        $qb = $this->createQueryBuilder('h');

        $qb
            ->delete(Hash::class)
            ->where(
                $qb->expr()->in('h.userId', $userIds)
            )
            ->getQuery()
            ->execute();
    }
}
