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

use App\Entity\ZzzItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository as ServiceEntityRepositoryAlias;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class ZzzItemRepository extends ServiceEntityRepositoryAlias
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZzzItems::class);
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumItems(int $itemId): int
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(i.itemId) FROM App\Entity\ZzzItems i
            WHERE i.itemId = :itemId
        ');
        $query->setParameters([
            'itemId' => $itemId,
        ]);

        return $query->getSingleScalarResult();
    }
}
