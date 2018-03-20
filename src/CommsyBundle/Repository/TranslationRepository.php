<?php

namespace CommsyBundle\Repository;

use CommsyBundle\Entity\Translation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * TranslationRepository
 */
class TranslationRepository extends EntityRepository
{
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
