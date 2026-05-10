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
use App\Room\RoomStatus;
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
                $qb->expr()->eq('IDENTITY(u.room)', ':contextId'),
                $qb->expr()->eq('u.status', ':status'),
                $qb->expr()->isNull('u.deletionDate'),
                $qb->expr()->isNull('u.deleter')
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
            ->andWhere('IDENTITY(u.room) = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    public function getContactsByRoomId(int $roomId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('u.isContact = 1')
            ->andWhere('IDENTITY(u.room) = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsers(int $contextId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('IDENTITY(u.room) = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsersAsQuery(int $contextId): mixed
    {
        return $this->createQueryBuilder('u')
            ->where('IDENTITY(u.room) = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery();
    }

    public function getNumActiveUsersByContext(int $contextId): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.itemId) as num')
            ->where('IDENTITY(u.room) = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPortalUser(Account $account): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('IDENTITY(u.room) = :contextId')
            ->andWhere('u.authSource = :authSourceId')
            ->andWhere('u.userId = :username')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $account->getPortal())
            ->setParameter('authSourceId', $account->getAuthSource()->getId())
            ->setParameter('username', $account->getUsername())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Resolves the {@see User} for an {@see Account} in the given context.
     * Generalizes {@see findPortalUser()}: pass the portal id to find the
     * portal-level user, or a room item_id to find the room-level user.
     *
     * Mirrors `cs_user_manager::getUserListByLimits` setup
     * (context_id + user_id + auth_source + alive). Returns null when the
     * account has no membership in that context.
     *
     * Used by the new {@see \App\Security\Permission} services as their
     * primary "who is this Account in this context?" lookup — replaces
     * `cs_user_item::getRelatedUserItemInContext()` in legacy-free code.
     */
    public function findInContext(Account $account, int $contextId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('IDENTITY(u.room) = :contextId')
            ->andWhere('u.authSource = :authSourceId')
            ->andWhere('u.userId = :username')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->setParameter('authSourceId', $account->getAuthSource()?->getId())
            ->setParameter('username', $account->getUsername())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Looks up the Doctrine User entity matching a legacy cs_user_item's
     * identity triple (userId + contextId + authSource). The new
     * Permission services consume Doctrine entities only — this is the
     * conversion seam used by the cs_item.may* wrappers during the
     * Phase 2/Phase 5 transition. Goes away once the legacy methods are
     * removed.
     */
    public function findOneByLegacyIdentity(string $userId, int $contextId, ?int $authSourceId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.userId = :userId')
            ->andWhere('IDENTITY(u.room) = :contextId')
            ->andWhere('u.authSource = :authSourceId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('contextId', $contextId)
            ->setParameter('authSourceId', $authSourceId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByRoomStatus(
        Account $account,
        string $filterArchived = 'all',
        string $filterLocked = 'all',
        string $filterType = 'all',
        string $filterUserStatus = 'all'
    ): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.room', 'r', Join::WITH)
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
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

        if ($filterLocked !== 'all') {
            if ($filterLocked === 'only') {
                $qb->andWhere($qb->expr()->in('r.status', ':statusValues'));
            } else if ($filterLocked === 'except') {
                $qb->andWhere($qb->expr()->notIn('r.status', ':statusValues'));
            }
            $qb->setParameter('statusValues', [RoomStatus::LOCKED->value, RoomStatus::LOCKED_PORTAL_MOD->value]);
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
