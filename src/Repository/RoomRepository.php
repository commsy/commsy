<?php
namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        if($sortExploded[0] === 'activity' || $sortExploded[0] === 'title'){
            $orderBy = 'r.'.$sortExploded[0];
        } else {
            $orderBy = 'r.activity';
        }

        $order = isset($sortExploded[1]) ? 'ASC' : 'DESC';

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('r.contextId', ':contextId'),
                $qb->expr()->in('r.type', $roomTypes),
                $qb->expr()->isNull('r.deletionDate'),
                $qb->expr()->isNull('r.deleter')
            ))
            ->orderBy($orderBy, $order)
            ->setParameters([
                'contextId' => $portalId,
            ]);
    }

    /**
     * @param string $oldState
     * @param string $newState
     * @return int|mixed|string
     */
    public function updateActivity(string $oldState, string $newState)
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
}