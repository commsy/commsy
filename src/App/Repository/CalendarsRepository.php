<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class CalendarsRepository extends EntityRepository
{
    public function findByRoomId($roomId)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.context_id = :roomId')
            ->setParameter('roomId', $roomId)
            ->getQuery();

        return $query->getResult();
    }
}