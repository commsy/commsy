<?php
namespace CommsyBundle\Repository;

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
}