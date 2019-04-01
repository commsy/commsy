<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class LabelRepository extends EntityRepository
{
    public function findRoomHashtags($roomId)
    {
        $query = $this->createQueryBuilder('l')
            ->andWhere('l.contextId = :roomId')
            ->andWhere('l.type = :type')
            ->andWhere('l.deletionDate IS NULL')
            ->andWhere('l.deleter IS NULL')
            ->setParameter('roomId', $roomId)
            ->setParameter('type', 'buzzword')
            ->getQuery();

        return $query->getResult();
    }

    public function findLabelsByContextIdAndNameAndType($contextId, $name, $type)
    {
        $qb = $this->createQueryBuilder('l');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('l.contextId', ':contextId'),
                $qb->expr()->eq('l.name', ':name'),
                $qb->expr()->eq('l.type', ':type'),
                $qb->expr()->isNull('l.deletionDate'),
                $qb->expr()->isNull('l.deleter')
            ))
            ->setParameters([
                'contextId' => $contextId,
                'name' => $name,
                'type' => $type,
            ])
            ->getQuery()
            ->getResult();
    }
}