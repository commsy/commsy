<?php

namespace App\Repository;

use App\Entity\Items;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository as ServiceEntityRepositoryAlias;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepositoryAlias
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Items::class);
    }

    /**
     * @param int $itemId
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumItems(int $itemId): int
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(i.itemId) FROM App\Entity\Items i
            WHERE i.itemId = :itemId
        ');
        $query->setParameters([
            'itemId' => $itemId,
        ]);

        return $query->getSingleScalarResult();
    }
}