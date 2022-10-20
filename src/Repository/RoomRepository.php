<?php
namespace App\Repository;

use App\Entity\Account;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
     * @param int $portalId portal ID
     * @param array $roomTypes array of room type strings ('project' and/or 'community'), indicating which rooms shall be returned
     * @param string $sort
     * @return QueryBuilder
     */
    public function getMainRoomQueryBuilder(int $portalId, array $roomTypes = ['project', 'community'], string $sort='activity'): QueryBuilder
    {
        // TODO: support portal settings option "All workspaces > Show templates in workplace list"

        $qb = $this->createQueryBuilder('r');

        $sortExploded = explode('_', $sort);

        if ($sortExploded[0] === 'activity' || $sortExploded[0] === 'title') {
            $orderBy = 'r.' . $sortExploded[0];
        } else {
            $orderBy = 'r.activity';
        }

        // NOTE: for activity, the sort order is switched around:
        //       $sort = 'activity' -> DESC
        //       $sort = 'activity_rev' -> ASC
        if (isset($sortExploded[1])) {
            $order = $sortExploded[0] === 'activity' ? 'ASC' : 'DESC';
        } else {
            $order = $sortExploded[0] === 'activity' ? 'DESC' : 'ASC';
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

    /**
     * NOTE: This may be used by a UniqueEntity/UniqueRoomSlug annotation in App\Entity\Room.
     *
     * @param array $fields associative array of room identifiers with keys: `slug`, `contextId`
     * @return Room|null
     * @throws NonUniqueResultException
     */
    public function findOneByRoomIdentifiersArray(array $fields): ?Room
    {
        return $this->findOneByRoomSlug($fields['slug'], $fields['contextId']);
    }

    /**
     * @param string $slug
     * @param int $context
     * @return Room|null
     * @throws NonUniqueResultException
     */
    public function findOneByRoomSlug(string $roomSlug, int $context): ?Room
    {
        return $this->createQueryBuilder('a')
            ->where('a.slug = :slug')
            ->andWhere('a.contextId = :contextId')
            ->setParameters([
                'slug' => $roomSlug,
                'contextId' => $context,
            ])
            ->getQuery()
            ->getOneOrNullResult();
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
