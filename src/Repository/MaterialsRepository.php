<?php
namespace App\Repository;

use App\Entity\Materials;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

class MaterialsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Materials::class);
    }

    public function findLatestVersionByItemId($itemId):? Materials
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
     * Used by Elastic when populating the index. The join will ensure only the latest version of a material
     * is index. Check the answer at StackOverflow for details on the greatest-n-per-group query.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createSearchQueryBuilder()
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
     *
     * @param string $entityAlias
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createSearchHydrationQueryBuilder(string $entityAlias)
    {
        return $this->createQueryBuilder($entityAlias)
            ->leftJoin(
                Materials::class,
                'b',
                Expr\Join::WITH,
                $entityAlias . '.itemId = b.itemId AND ' . $entityAlias . '.versionId < b.versionId')
            ->where('b.itemId IS NULL');
    }
}