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

use App\Entity\Files;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class FilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Files::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getNumFiles(int $fileId, int $contextId): bool|float|int|string
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(f.filesId) FROM App\Entity\Files f
            WHERE f.filesId = :filesId AND f.contextId = :contextId
        ');
        $query->setParameters(new ArrayCollection([
            new Parameter('filesId', $fileId),
            new Parameter('contextId', $contextId),
        ]));

        return $query->getSingleScalarResult();
    }

    public function findAllLockedBefore(DateTime $threshold): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT f FROM App\Entity\Files f
            WHERE f.lockingDate < :threshold
        ');
        $query->setParameter('threshold', $threshold);

        return $query->getResult();
    }
}
