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

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Items;
use App\Entity\Discussionarticles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the articles of a discussion.
 *
 * They are kept flat here. The thread is not a table structure but a path
 * spelled out in `position` — `0001.0002` is the second reply to the first
 * article — so an importer can rebuild the tree without the export nesting it.
 *
 * @extends ServiceEntityRepository<Discussionarticles>
 */
class DiscussionarticlesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussionarticles::class);
    }

    /**
     * A sub-entry can be a draft of its own while its parent is not, so the
     * draft flag is asked here as well as on the parent.
     *
     * @return Discussionarticles[] in thread order, which is what `position` spells out
     */
    public function findForExport(int $discussionId): array
    {
        return $this->createQueryBuilder('a')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = a.itemId')
            ->andWhere('a.discussionId = :discussionId')
            ->andWhere('a.deleter IS NULL')
            ->andWhere('a.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL')
            ->setParameter('discussionId', $discussionId)
            ->orderBy('a.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
