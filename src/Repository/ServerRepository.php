<?php


namespace App\Repository;


use App\Entity\Server;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    /**
     * @return Server
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getServer(): Server
    {
        return $this->createQueryBuilder('s')
            ->where('s.deleterId IS NULL')
            ->andWhere('s.deletionDate IS NULL')
            ->andWhere('s.itemId = :serverId')
            ->setParameter('serverId', 99)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}