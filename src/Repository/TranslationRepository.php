<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TranslationRepository.
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    /**
     * Returns a single translation by context and translation key.
     *
     * @return Translation
     *
     * @throws NonUniqueResultException
     */
    public function findOneByContextAndKey(int $contextId, string $key)
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
