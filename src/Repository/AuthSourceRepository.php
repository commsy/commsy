<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.07.18
 * Time: 11:42
 */

namespace App\Repository;

use App\Entity\AuthSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuthSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthSource::class);
    }

    /**
     * @param int $portalId
     * @return mixed
     */
    public function findByPortal(int $portalId)
    {
        return $this->createQueryBuilder('a')
            ->where('a.deleterId IS NULL')
            ->andWhere('a.deletionDate IS NULL')
            ->andWhere('a.contextId = :portalId')
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->getResult();
    }
}