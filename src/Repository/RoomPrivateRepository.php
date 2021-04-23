<?php

namespace App\Repository;

use App\Entity\RoomPrivat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

class RoomPrivateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomPrivat::class);
    }

    /**
     * @param int $contextId
     * @param string $username
     * @return RoomPrivat|null
     * @throws NonUniqueResultException
     */
    public function findByContextIdAndUsername(int $contextId, string $username):? RoomPrivat
    {
        return $this->createQueryBuilder('rp')
            ->select('rp')
            ->innerJoin('App:User', 'u', Expr\Join::WITH, 'u.contextId = rp.itemId')
            ->innerJoin('App:Account', 'a', Expr\Join::WITH, 'a.username = u.userId')
            ->where('rp.contextId = :contextId')
            ->andWhere('rp.deleterId IS NULL')
            ->andWhere('rp.deletionDate IS NULL')
            ->andWhere('u.userId = :username')
            ->andWhere('a.contextId = :contextId')
            ->setParameters([
                'contextId' => $contextId,
                'username' => $username,
            ])
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}