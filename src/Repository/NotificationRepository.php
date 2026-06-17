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
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $notification): void
    {
        $em = $this->getEntityManager();
        $em->persist($notification);
        $em->flush();
    }

    public function remove(Notification $notification): void
    {
        $em = $this->getEntityManager();
        $em->remove($notification);
        $em->flush();
    }

    /**
     * Unread count for the activity indicator, optionally scoped to one room.
     */
    public function countUnreadForAccount(Account $account, ?int $contextId = null): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :account')->setParameter('account', $account)
            ->andWhere('n.readAt IS NULL');

        if ($contextId !== null) {
            $qb->andWhere('n.contextId = :ctx')->setParameter('ctx', $contextId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Notifications for the activity panel, newest first, optionally scoped to
     * one room. Every stored row is undismissed (dismiss deletes the row), so no
     * extra state filter is needed.
     *
     * @return Notification[]
     */
    public function findForAccount(Account $account, ?int $contextId = null, int $limit = 50): array
    {
        return $this->accountQuery($account, false, $contextId)
            ->orderBy('n.createdAt', 'DESC')->addOrderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Dismiss (delete) a single notification, scoped to its owner so a recipient
     * can only ever drop their own.
     *
     * @return int number of rows deleted (0 or 1)
     */
    public function dismiss(Account $account, int $id): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('DELETE App\Entity\Notification n WHERE n.id = :id AND n.recipient = :account')
            ->setParameter('id', $id)
            ->setParameter('account', $account)
            ->execute();
    }

    /**
     * Dismiss (delete) every notification of an account.
     *
     * @return int number of rows deleted
     */
    public function dismissAllForAccount(Account $account): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('DELETE App\Entity\Notification n WHERE n.recipient = :account')
            ->setParameter('account', $account)
            ->execute();
    }

    /**
     * Dismiss (delete) every notification of an account within one room.
     *
     * @return int number of rows deleted
     */
    public function dismissAllForAccountAndContext(Account $account, int $contextId): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('DELETE App\Entity\Notification n WHERE n.recipient = :account AND n.contextId = :ctx')
            ->setParameter('account', $account)
            ->setParameter('ctx', $contextId)
            ->execute();
    }

    /**
     * Newest notifications for the dropdown, newest first.
     *
     * @return Notification[]
     */
    public function findLatestForAccount(Account $account, int $limit): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :account')->setParameter('account', $account)
            ->orderBy('n.createdAt', 'DESC')->addOrderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * One page of notifications for the standalone list, newest first.
     *
     * @return Notification[]
     */
    public function findForAccountPaginated(
        Account $account,
        int $page,
        int $perPage,
        bool $unreadOnly = false,
        ?int $contextId = null,
    ): array {
        return $this->accountQuery($account, $unreadOnly, $contextId)
            ->orderBy('n.createdAt', 'DESC')->addOrderBy('n.id', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countForAccount(Account $account, bool $unreadOnly = false, ?int $contextId = null): int
    {
        return (int) $this->accountQuery($account, $unreadOnly, $contextId)
            ->select('COUNT(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function existsForSourceItem(int $sourceItemId): bool
    {
        $count = (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.sourceItemId = :id')->setParameter('id', $sourceItemId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Mark every unread notification of an account read in one statement.
     *
     * @return int number of rows updated
     */
    public function markAllReadForAccount(Account $account, \DateTimeImmutable $now): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'UPDATE App\Entity\Notification n
                 SET n.readAt = :now
                 WHERE n.recipient = :account AND n.readAt IS NULL'
            )
            ->setParameter('now', $now)
            ->setParameter('account', $account)
            ->execute();
    }

    /**
     * Drop notifications pointing at an item that no longer exists.
     *
     * @return int number of rows deleted
     */
    public function removeForSourceItem(int $sourceItemId): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('DELETE App\Entity\Notification n WHERE n.sourceItemId = :id')
            ->setParameter('id', $sourceItemId)
            ->execute();
    }

    /**
     * Retention sweep: drop already-read notifications older than a cutoff.
     *
     * @return int number of rows deleted
     */
    public function removeReadOlderThan(\DateTimeImmutable $cutoff): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'DELETE App\Entity\Notification n
                 WHERE n.readAt IS NOT NULL AND n.readAt < :cutoff'
            )
            ->setParameter('cutoff', $cutoff)
            ->execute();
    }

    /**
     * Active dismissal: drop notifications created before a cutoff, regardless of
     * read state. Backs the 30-day auto-dismiss cron.
     *
     * @return int number of rows deleted
     */
    public function removeOlderThan(\DateTimeImmutable $cutoff): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('DELETE App\Entity\Notification n WHERE n.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->execute();
    }

    private function accountQuery(Account $account, bool $unreadOnly, ?int $contextId): QueryBuilder
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :account')->setParameter('account', $account);

        if ($unreadOnly) {
            $qb->andWhere('n.readAt IS NULL');
        }

        if ($contextId !== null) {
            $qb->andWhere('n.contextId = :ctx')->setParameter('ctx', $contextId);
        }

        return $qb;
    }
}
