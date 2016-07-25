<?php
namespace CommsyBundle\Repository;

use Doctrine\ORM\EntityRepository;

class RoomRepository extends EntityRepository
{
    public function getMainRoomQueryBuilder($portalId)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('r.contextId', ':contextId'),
                $qb->expr()->orX(
                    $qb->expr()->eq('r.type', ':type_project'),
                    $qb->expr()->eq('r.type', ':type_community')
                ),
                $qb->expr()->isNull('r.deletionDate'),
                $qb->expr()->isNull('r.deleter')
            ))
            ->orderBy('r.activity', 'DESC')
            ->setParameters([
                'contextId' => $portalId,
                'type_project' => 'project',
                'type_community' => 'community',
            ]);
    }
}