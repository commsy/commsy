<?php
namespace CommsyBundle\Repository;

use CommsyBundle\Entity\Materials;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

class MaterialsRepository extends EntityRepository
{
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
                'CommsyBundle:Materials',
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
                'CommsyBundle:Materials',
                'b',
                Expr\Join::WITH,
                $entityAlias . '.itemId = b.itemId AND ' . $entityAlias . '.versionId < b.versionId')
            ->where('b.itemId IS NULL');
    }
}