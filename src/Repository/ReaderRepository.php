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

use App\Entity\Reader;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReaderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reader::class);
    }

    public function findOneByItemIdAndUserId(int $itemId, int $userId): ?Reader
    {
        $query = $this->getEntityManager()
            ->createQuery("
                SELECT r
                FROM App\Entity\Reader r
                WHERE r.itemId = :itemId AND r.userId = :userId
            ")
            ->setParameter('itemId', $itemId)
            ->setParameter('userId', $userId);

        return $query->getOneOrNullResult();
    }
}
