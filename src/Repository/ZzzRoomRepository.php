<?php
namespace App\Repository;

use App\Entity\ZzzRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ZzzRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZzzRoom::class);
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
}