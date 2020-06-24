<?php
namespace App\Repository;

use App\Entity\Calendars;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CalendarsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calendars::class);
    }

    public function findByRoomId($roomId)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.context_id = :roomId')
            ->setParameter('roomId', $roomId)
            ->getQuery();

        return $query->getResult();
    }
}