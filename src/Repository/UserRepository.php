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

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getConfirmableUserByContextId($contextId)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('r.contextId', ':contextId'),
                $qb->expr()->eq('r.status', ':status'),
                $qb->expr()->isNull('r.deletionDate'),
                $qb->expr()->isNull('r.deleterId')
            ))
            ->setParameters([
                'contextId' => $contextId,
                'status' => 1,
            ]);
    }

    /**
     * @return mixed
     */
    public function getModeratorsByRoomId(int $roomId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = 3')
            ->andWhere('u.contextId = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     */
    public function getContactsByRoomId(int $roomId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.isContact = 1')
            ->andWhere('u.contextId = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     */
    public function findActiveUsers(int $contextId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     */
    public function findActiveUsersAsQuery(int $contextId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery();
    }

    public function getNumActiveUsersByContext(int $contextId): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.itemId) as num')
            ->where('u.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPortalUser(Account $account): User
    {
        return $this->createQueryBuilder('u')
            ->where('u.contextId = :contextId')
            ->andWhere('u.authSource = :authSourceId')
            ->andWhere('u.userId = :username')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $account->getContextId())
            ->setParameter('authSourceId', $account->getAuthSource()->getId())
            ->setParameter('username', $account->getUsername())
            ->getQuery()
            ->getSingleResult();
    }
}
