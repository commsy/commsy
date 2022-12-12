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
     * @return array
     *
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
