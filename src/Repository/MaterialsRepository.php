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

use App\Entity\Items;
use App\Entity\Materials;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Materials>
 */
class MaterialsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Materials::class);
    }

    public function findLatestVersionByItemId($itemId): ?Materials
    {
        return $this->createQueryBuilder('m')
            ->leftJoin(
                Materials::class,
                'b',
                Expr\Join::WITH,
                'm.itemId = b.itemId AND m.versionId < b.versionId')
            ->where('b.itemId IS NULL')
            ->andWhere('m.itemId = :itemId')
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The materials an export of this room carries, each at its newest version.
     *
     * Older versions are history the room does not show, and what hangs off an
     * entry — attachments, hashtags — belongs to the version it was written on.
     *
     * Deleted entries and drafts stay out, the draft flag being a column on
     * `items` rather than on the rubric.
     *
     * @return Materials[] oldest first, a window of them if $limit is given
     */
    public function findForExport(int $contextId, int $offset = 0, ?int $limit = null): array
    {
        return $this->exportable()
            ->andWhere('m.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->orderBy('m.itemId', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * How many entries {@see self::findForExport()} holds in all.
     */
    public function countForExport(int $contextId): int
    {
        return (int) $this->exportable()
            ->select('COUNT(m.itemId)')
            ->andWhere('m.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * One material by id, at the version the room shows, on the same terms as
     * {@see self::findForExport()}.
     */
    public function findOneForExport(int $itemId): ?Materials
    {
        return $this->exportable()
            ->andWhere('m.itemId = :itemId')
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function exportable(): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->leftJoin(
                Materials::class,
                'newer',
                Expr\Join::WITH,
                'm.itemId = newer.itemId AND m.versionId < newer.versionId'
            )
            ->join(Items::class, 'i', Expr\Join::WITH, 'i.itemId = m.itemId')
            ->where('newer.itemId IS NULL')
            ->andWhere('m.deleter IS NULL')
            ->andWhere('m.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL');
    }

    /**
     * Used by Elastic when populating the index. The join will ensure only the latest version of a material
     * is index. Check the answer at StackOverflow for details on the greatest-n-per-group query.
     */
    public function createSearchQueryBuilder(): QueryBuilder
    {
        // @see: https://stackoverflow.com/a/7745635
        return $this->createQueryBuilder('m')
            ->leftJoin(
                Materials::class,
                'b',
                Expr\Join::WITH,
                'm.itemId = b.itemId AND m.versionId < b.versionId')
            ->where('b.itemId IS NULL');
    }

    /**
     * Used by Elastica to transform results to model. We use the join to ensure only the latest version of a material
     * is in the result set.
     */
    public function createSearchHydrationQueryBuilder(string $entityAlias): QueryBuilder
    {
        return $this->createQueryBuilder($entityAlias)
            ->leftJoin(
                Materials::class,
                'b',
                Expr\Join::WITH,
                $entityAlias.'.itemId = b.itemId AND '.$entityAlias.'.versionId < b.versionId')
            ->where('b.itemId IS NULL');
    }
}
