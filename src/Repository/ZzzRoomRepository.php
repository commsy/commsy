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
     * @return QueryBuilder
     */
    public function getMainRoomQueryBuilder(int $portalId, array $roomTypes = ['project', 'community']): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('r.contextId', ':contextId'),
                $qb->expr()->in('r.type', $roomTypes),
                $qb->expr()->isNull('r.deletionDate'),
                $qb->expr()->isNull('r.deleter')
            ))
            ->orderBy('r.activity', 'DESC')
            ->setParameters([
                'contextId' => $portalId,
            ]);
    }
}