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

use App\Entity\Labels;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class LabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Labels::class);
    }

    public function findRoomHashtags($roomId)
    {
        $query = $this->createQueryBuilder('l')
            ->andWhere('l.contextId = :roomId')
            ->andWhere('l.type = :type')
            ->andWhere('l.deletionDate IS NULL')
            ->andWhere('l.deleter IS NULL')
            ->setParameter('roomId', $roomId)
            ->setParameter('type', 'buzzword')
            ->getQuery();

        return $query->getResult();
    }

    public function findLabelsByContextIdAndNameAndType($contextId, $name, $type)
    {
        $qb = $this->createQueryBuilder('l');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('l.contextId', ':contextId'),
                $qb->expr()->eq('l.name', ':name'),
                $qb->expr()->eq('l.type', ':type'),
                $qb->expr()->isNull('l.deletionDate'),
                $qb->expr()->isNull('l.deleter')
            ))
            ->setParameters(new ArrayCollection([
                new Parameter('contextId', $contextId),
                new Parameter('name', $name),
                new Parameter('type', $type),
            ]))
            ->getQuery()
            ->getResult();
    }
}
