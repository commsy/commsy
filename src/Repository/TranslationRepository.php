<?php

namespace App\Repository;

use App\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TranslationRepository
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    /**
     * Returns a single translation by context and translation key
     *
     * @param int $contextId
     * @param string $key
     *
     * @return Translation
     * @throws NonUniqueResultException
     */
    public function findOneByContextAndKey($contextId, $key)
    {
        return $this->createQueryBuilder('translation')
            ->select()
            ->where('translation.contextId = :context_id')
            ->andWhere('translation.translationKey = :translation_key')
            ->setParameter('context_id', $contextId)
            ->setParameter('translation_key', $key)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
