<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\RoomPrivat;
use App\Entity\User;
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
     * @param int $portalId
     * @param Account $account
     * @return RoomPrivat|null
     * @throws NonUniqueResultException
     */
    public function findOneByPortalIdAndAccount(int $portalId, Account $account): ?RoomPrivat
    {
        return $this->createQueryBuilder('rp')
            ->select('rp')
            ->innerJoin(User::class, 'u', Expr\Join::WITH, 'u.contextId = rp.itemId AND u.deleterId IS NULL AND u.deletionDate IS NULL')
            ->innerJoin(Account::class, 'a', Expr\Join::WITH, 'a.username = u.userId AND a.authSource = u.authSource')
            ->where('rp.contextId = :portalId')
            ->andWhere('rp.deleterId IS NULL')
            ->andWhere('rp.deletionDate IS NULL')
            ->andWhere('a.authSource = :authSource')
            ->andWhere('a.contextId = :portalId')
            ->andWhere('a.username = :username')
            ->setParameters([
                'portalId' => $portalId,
                'username' => $account->getUsername(),
                'authSource' => $account->getAuthSource()
            ])
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}