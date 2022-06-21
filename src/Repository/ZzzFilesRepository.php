<?php

namespace App\Repository;

use App\Entity\ZzzFiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class ZzzFilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZzzFiles::class);
    }

    /**
     * @param int $fileId
     * @param int $contextId
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumFiles(int $fileId, int $contextId): int
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(f.filesId) FROM App\Entity\ZzzFiles f
            WHERE f.filesId = :filesId AND f.contextId = :contextId
        ');
        $query->setParameters([
            'filesId' => $fileId,
            'contextId' => $contextId,
        ]);

        return $query->getSingleScalarResult();
    }
}