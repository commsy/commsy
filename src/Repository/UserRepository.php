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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getConfirmableUserByContextId($contextId)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('u.context', ':contextId'),
                $qb->expr()->eq('u.status', ':status'),
                $qb->expr()->isNull('u.deletionDate'),
                $qb->expr()->isNull('u.deleterId')
            ))
            ->setParameters(new ArrayCollection([
                new Parameter('contextId', $contextId),
                new Parameter('status', 1),
            ]));
    }

    public function getModeratorsByRoomId(int $roomId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = 3')
            ->andWhere('u.context = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    public function getContactsByRoomId(int $roomId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('u.isContact = 1')
            ->andWhere('u.context = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsers(int $contextId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('u.context = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsersAsQuery(int $contextId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('u.context = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery();
    }

    public function getNumActiveUsersByContext(int $contextId): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.itemId) as num')
            ->where('u.context = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPortalUser(Account $account): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.context = :contextId')
            ->andWhere('u.authSource = :authSourceId')
            ->andWhere('u.userId = :username')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $account->getContextId())
            ->setParameter('authSourceId', $account->getAuthSource()->getId())
            ->setParameter('username', $account->getUsername())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByRoomStatus(
        Account $account,
        string $filterArchived = 'all',
        string $filterType = 'all',
        string $filterUserStatus = 'all'
    ): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.context', 'r', Join::WITH)
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->andWhere('u.userId = :userId')
            ->andWhere('u.authSource = :authSource')
            ->setParameters(new ArrayCollection([
                new Parameter('userId', $account->getUsername()),
                new Parameter('authSource', $account->getAuthSource()),
            ]));

        if ($filterArchived !== 'all') {
            $qb->andWhere('r.archived = :archived');
            $qb->setParameter('archived', $filterArchived === 'only');
        }

        if ($filterType !== 'all') {
            $qb->andWhere('r.type = :type');
            $qb->setParameter('type', $filterType);
        }

        if ($filterUserStatus !== 'all') {
            $qb->andWhere('u.status = :status');
            $qb->setParameter('status', $filterUserStatus);
        }

        return $qb->getQuery()->execute();
    }
}
