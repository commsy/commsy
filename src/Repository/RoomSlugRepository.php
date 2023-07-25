<?php

namespace App\Repository;

use App\Entity\RoomSlug;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoomSlug>
 *
 * @method RoomSlug|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomSlug|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomSlug[]    findAll()
 * @method RoomSlug[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomSlugRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomSlug::class);
    }

    public function save(RoomSlug $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RoomSlug $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
