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
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /**
     * Returns a new QueryBuilder instance with a query that returns all non-deleted project and/or community rooms
     * for the given portal ID.
     *
     * @param int   $portalId  portal ID
     * @param array $roomTypes array of room type strings ('project' and/or 'community'), indicating which rooms shall be returned
     */
    public function getMainRoomQueryBuilder(int $portalId, array $roomTypes = ['project', 'community'], string $sort = 'activity'): QueryBuilder
    {
        // TODO: support portal settings option "All workspaces > Show templates in workplace list"

        $qb = $this->createQueryBuilder('r');

        $sortExploded = explode('_', $sort);

        if ('activity' === $sortExploded[0] || 'title' === $sortExploded[0]) {
            $orderBy = 'r.'.$sortExploded[0];
        } else {
            $orderBy = 'r.activity';
        }

        // NOTE: for activity, the sort order is switched around:
        //       $sort = 'activity' -> DESC
        //       $sort = 'activity_rev' -> ASC
        if (isset($sortExploded[1])) {
            $order = 'activity' === $sortExploded[0] ? 'ASC' : 'DESC';
        } else {
            $order = 'activity' === $sortExploded[0] ? 'DESC' : 'ASC';
        }

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('r.contextId', ':contextId'),
                $qb->expr()->in('r.type', $roomTypes),
                $qb->expr()->isNull('r.deletionDate'),
                $qb->expr()->isNull('r.deleter')
            ))
            ->orderBy($orderBy, $order)
            ->addOrderBy('r.template', 'ASC')
            ->setParameters([
                'contextId' => $portalId,
            ]);
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumActiveRoomsByPortal(int $portalId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.itemId) as num')
            ->where('r.contextId = :portalId')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.deleter IS NULL')
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getActiveRoomsByAccount(Account $account)
    {
        return $this->createQueryBuilder('r')
            ->select()
            ->innerJoin(User::class, 'u', Join::WITH, 'u.contextId = r.itemId')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('r.deleter IS NULL')
            ->andWhere('r.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->andWhere('u.userId = :userId')
            ->andWhere('u.authSource = :authSource')
            ->setParameter(':contextId', $account->getContextId())
            ->setParameter(':userId', $account->getUsername())
            ->setParameter(':authSource', $account->getAuthSource()->getId())
            ->getQuery()
            ->getResult();
    }

    public function getProjectAndUserRoomIds(): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT r.itemId FROM App\Entity\Room r
            WHERE (r.type = :projectType OR r.type = :userroomType)
        ');
        $query->setParameters([
            'projectType' => 'project',
            'userroomType' => 'userroom',
        ]);

        return array_column($query->getResult(), 'itemId');
    }

    public function updateActivity(string $oldState, string $newState): mixed
    {
        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.activityState', ':newState')
            ->where('r.activityState = :oldState')
            ->setParameter('oldState', $oldState)
            ->setParameter('newState', $newState)
            ->getQuery()
            ->execute();
    }

    public function countByPortalAndType()
    {
        return $this->createQueryBuilder('r')
            ->groupBy('r.contextId')
            ->addGroupBy('r.type')
            ->select('COUNT(r) as count', 'r.type', 'p.title as portal')
            ->innerJoin(Portal::class, 'p', Join::WITH, 'r.contextId = p.id')
            ->andWhere('r.deleter IS NULL')
            ->andWhere('r.deletionDate IS NULL')
            ->andWhere('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery()
            ->getResult();
    }
}
