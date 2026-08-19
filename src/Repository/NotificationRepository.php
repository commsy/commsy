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
use App\Enum\NotificationAction;
use App\Enum\NotificationType;
use App\Notification\RoomActivitySummary;
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
     * Unread count for an indicator, optionally scoped to one room and to
     * certain types (the bell counts everything except content activity).
     *
     * @param NotificationType[] $types empty means every type
     */
    public function countUnreadForAccount(Account $account, ?int $contextId = null, array $types = []): int
    {
        return (int) $this->accountQuery($account, true, $contextId, $types)
            ->select('COUNT(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Notifications for the activity panel, newest first, optionally scoped to
     * one room. Read and unread rows alike are listed; rows only ever leave the
     * panel through the retention cron (or when their item is deleted).
     *
     * @param NotificationType[] $types empty means every type
     *
     * @return Notification[]
     */
    public function findForAccount(Account $account, ?int $contextId = null, int $limit = 50, array $types = []): array
    {
        return $this->accountQuery($account, false, $contextId, $types)
            ->orderBy('n.createdAt', 'DESC')->addOrderBy('n.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
     * Per-event idempotency guard: has the event for this item at this exact
     * time already been fanned out? Distinct edits happen at distinct times and
     * are logged separately; only a messenger retry of the same event matches.
     */
    public function existsForSourceItemAt(int $sourceItemId, \DateTimeImmutable $occurredAt): bool
    {
        $count = (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.sourceItemId = :id')->setParameter('id', $sourceItemId)
            ->andWhere('n.createdAt = :at')->setParameter('at', $occurredAt)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Mark every unread notification of an account read in one statement,
     * optionally limited to one room (the room panel's mark-all action) and to
     * certain types — open tasks must stay unread, or the bell would stop
     * counting work that is still waiting.
     *
     * @param NotificationType[] $types empty means every type
     *
     * @return int number of rows updated
     */
    public function markAllReadForAccount(Account $account, \DateTimeImmutable $now, ?int $contextId = null, array $types = []): int
    {
        $dql = 'UPDATE App\Entity\Notification n
                SET n.readAt = :now
                WHERE n.recipient = :account AND n.readAt IS NULL';

        if ($contextId !== null) {
            $dql .= ' AND n.contextId = :ctx';
        }

        if ($types !== []) {
            $dql .= ' AND n.type IN (:types)';
        }

        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameter('now', $now)
            ->setParameter('account', $account);

        if ($contextId !== null) {
            $query->setParameter('ctx', $contextId);
        }

        if ($types !== []) {
            $query->setParameter('types', $types);
        }

        return (int) $query->execute();
    }

    /**
     * Mark every unread notification an account holds for one source item read.
     * Backs "opening the entry's detail page marks it read", regardless of how
     * the page was reached. Covers all events for the item (create and edits).
     *
     * @return int number of rows updated
     */
    public function markReadForAccountAndSourceItem(Account $account, int $sourceItemId, \DateTimeImmutable $now): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'UPDATE App\Entity\Notification n
                 SET n.readAt = :now
                 WHERE n.recipient = :account AND n.sourceItemId = :item AND n.readAt IS NULL'
            )
            ->setParameter('now', $now)
            ->setParameter('account', $account)
            ->setParameter('item', $sourceItemId)
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
     * Unread entry activity per room, condensed for the bell: one summary per
     * room, counting distinct entries per action so repeated edits of the same
     * entry stay a single number.
     *
     * @return RoomActivitySummary[] newest activity first
     */
    public function summariseUnreadEntryActivity(Account $account, int $roomLimit = 20): array
    {
        $rows = $this->createQueryBuilder('n')
            ->select('n.contextId AS contextId', 'n.roomTitle AS roomTitle', 'n.action AS action')
            ->addSelect('COUNT(DISTINCT n.sourceItemId) AS entries', 'MAX(n.createdAt) AS newestAt')
            ->andWhere('n.recipient = :account')->setParameter('account', $account)
            ->andWhere('n.type = :type')->setParameter('type', NotificationType::Entry)
            ->andWhere('n.readAt IS NULL')
            ->groupBy('n.contextId')->addGroupBy('n.roomTitle')->addGroupBy('n.action')
            ->getQuery()
            ->getResult();

        /** @var array<int, array{title: string, counts: array<string, int>, newest: string}> $byRoom */
        $byRoom = [];
        foreach ($rows as $row) {
            $contextId = (int) $row['contextId'];
            $action = $row['action'] instanceof NotificationAction ? $row['action']->value : (string) $row['action'];

            $byRoom[$contextId]['title'] = (string) $row['roomTitle'];
            $byRoom[$contextId]['counts'][$action] = (int) $row['entries'];
            $newest = (string) $row['newestAt'];
            if (!isset($byRoom[$contextId]['newest']) || $newest > $byRoom[$contextId]['newest']) {
                $byRoom[$contextId]['newest'] = $newest;
            }
        }

        $summaries = [];
        foreach ($byRoom as $contextId => $room) {
            $summaries[] = new RoomActivitySummary(
                $contextId,
                $room['title'],
                $room['counts'][NotificationAction::Created->value] ?? 0,
                $room['counts'][NotificationAction::Edited->value] ?? 0,
                $room['counts'][NotificationAction::Annotated->value] ?? 0,
                new \DateTimeImmutable($room['newest']),
            );
        }

        usort($summaries, static fn (RoomActivitySummary $a, RoomActivitySummary $b): int => $b->newestAt <=> $a->newestAt);

        return array_slice($summaries, 0, $roomLimit);
    }

    /**
     * Is there still an open notification of this type for the source item?
     * Backs "was this join request already decided?".
     */
    public function existsOfTypeForSourceItem(NotificationType $type, int $sourceItemId): bool
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.type = :type')->setParameter('type', $type)
            ->andWhere('n.sourceItemId = :item')->setParameter('item', $sourceItemId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Resolve a task: drop every recipient's notification of this type for the
     * source item, so a decision made by one moderator clears it for all.
     *
     * @return int number of rows deleted
     */
    public function removeOfTypeForSourceItem(NotificationType $type, int $sourceItemId): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('DELETE App\Entity\Notification n WHERE n.type = :type AND n.sourceItemId = :item')
            ->setParameter('type', $type)
            ->setParameter('item', $sourceItemId)
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
     * read state. Backs the 30-day auto-dismiss cron, which passes the types that
     * may age out — an undecided task must never disappear on its own.
     *
     * @param NotificationType[] $types empty means every type
     *
     * @return int number of rows deleted
     */
    public function removeOlderThan(\DateTimeImmutable $cutoff, array $types = []): int
    {
        $dql = 'DELETE App\Entity\Notification n WHERE n.createdAt < :cutoff';

        if ($types !== []) {
            $dql .= ' AND n.type IN (:types)';
        }

        $query = $this->getEntityManager()->createQuery($dql)->setParameter('cutoff', $cutoff);

        if ($types !== []) {
            $query->setParameter('types', $types);
        }

        return (int) $query->execute();
    }

    /**
     * @param NotificationType[] $types empty means every type
     */
    private function accountQuery(Account $account, bool $unreadOnly, ?int $contextId, array $types = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :account')->setParameter('account', $account);

        if ($unreadOnly) {
            $qb->andWhere('n.readAt IS NULL');
        }

        if ($contextId !== null) {
            $qb->andWhere('n.contextId = :ctx')->setParameter('ctx', $contextId);
        }

        if ($types !== []) {
            $qb->andWhere('n.type IN (:types)')->setParameter('types', $types);
        }

        return $qb;
    }
}
