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
            ->andWhere('IDENTITY(u.account) = :accountId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $account->getPortal())
            ->setParameter('accountId', $account->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Resolves the {@see User} for an {@see Account} in the given context.
     * Generalizes {@see findPortalUser()}: pass the portal id to find the
     * portal-level user, or a room item_id to find the room-level user.
     *
     * Returns null when the account has no membership in that context.
     *
     * Used by the {@see \App\Security\Permission} services as their primary
     * "who is this Account in this context?" lookup.
     */
    public function findInContext(Account $account, int $contextId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('IDENTITY(u.room) = :contextId')
            ->andWhere('IDENTITY(u.account) = :accountId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->setParameter('accountId', $account->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Looks up the Doctrine User entity for a legacy {@see \cs_user_item}'s
     * account binding in a given context. The new Permission services
     * consume Doctrine entities only — this is the conversion seam used by
     * the legacy bridge.
     */
    public function findByAccountIdAndContext(int $accountId, int $contextId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('IDENTITY(u.account) = :accountId')
            ->andWhere('IDENTITY(u.room) = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('accountId', $accountId)
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds non-soft-deleted **orphan** user rows for a given username inside
     * a portal — rows whose `account_id` is NULL, i.e. that no specific
     * account claims any more.
     *
     * Building block for two safety nets:
     *  - The Phase 1 sweep in {@see \App\Account\AccountDeleter::delete}
     *    catches orphans that share the deleted account's username in its
     *    portal before the account row is removed.
     *  - The fail-loud guard in
     *    {@see \App\Facade\AccountCreatorFacade::persistNewAccount} aborts
     *    the signup when an orphan with the new account's username already
     *    sits in the target portal.
     *
     * Rows that legitimately belong to a *different* account in the same
     * portal (possible when accounts share a username across different
     * auth sources) are intentionally excluded — they are not orphans and
     * must not be touched.
     *
     * @return User[]
     */
    public function findActiveOrphansByUsernameInPortal(string $username, int $portalId): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.userId = :username')
            ->andWhere('IDENTITY(u.portal) = :portalId')
            ->andWhere('u.account IS NULL')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleter IS NULL')
            ->setParameter('username', $username)
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->getResult();
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
            ->andWhere('IDENTITY(u.account) = :accountId')
            ->setParameters(new ArrayCollection([
                new Parameter('accountId', $account->getId()),
            ]));

        if ($filterArchived !== 'all') {
            $qb->andWhere('r.archived = :archived');
            $qb->setParameter('archived', $filterArchived === 'only');
        }

        // Explicit allowlist — bare `!== 'all'` previously bound
        // :statusValues even when no WHERE consumed it (Doctrine crash).
        if ($filterLocked === 'only' || $filterLocked === 'except') {
            $expr = $filterLocked === 'only'
                ? $qb->expr()->in('r.status', ':statusValues')
                : $qb->expr()->notIn('r.status', ':statusValues');
            $qb->andWhere($expr)
                ->setParameter('statusValues', [RoomStatus::LOCKED->value, RoomStatus::LOCKED_PORTAL_MOD->value]);
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
