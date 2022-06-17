<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.07.18
 * Time: 12:11
 */

namespace App\Repository;

use App\Entity\Portal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class PortalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portal::class);
    }

    public function findActivePortals()
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $portalId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findActivePortal(int $portalId): ?Portal
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->andWhere('p.id = :portalId')
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findAllActive()
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery()
            ->getResult();
    }
}