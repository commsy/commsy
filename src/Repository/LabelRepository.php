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
use App\Entity\Labels;
use App\Rubric\Label\LabelType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Labels>
 */
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

    /**
     * The labels an export of this room carries.
     *
     * Hashtags are left out: they stand on the room as its vocabulary, and an
     * entry points at them by id. What remains are the labels that are entries
     * of their own — topics, groups, institutions — named by {@see LabelType},
     * which is also what keeps the nameless rows of the untyped legacy subtype
     * out: no rubric lists them.
     *
     * Deleted entries and drafts stay out, the draft flag being a column on
     * `items` rather than on the rubric.
     *
     * @return Labels[] oldest first, a window of them if $limit is given
     */
    public function findForExport(int $contextId, int $offset = 0, ?int $limit = null): array
    {
        return $this->exportable()
            ->andWhere('l.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->orderBy('l.itemId', 'ASC')
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
            ->select('COUNT(l.itemId)')
            ->andWhere('l.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * One label by id, on the same terms as {@see self::findForExport()}.
     */
    public function findOneForExport(int $itemId): ?Labels
    {
        return $this->exportable()
            ->andWhere('l.itemId = :itemId')
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function exportable(): QueryBuilder
    {
        $types = array_map(
            static fn (LabelType $type): string => $type->value,
            array_filter(LabelType::cases(), static fn (LabelType $type): bool => LabelType::Buzzword !== $type)
        );

        return $this->createQueryBuilder('l')
            ->join(Items::class, 'i', Join::WITH, 'i.itemId = l.itemId')
            ->andWhere('l.type IN (:types)')
            ->andWhere('l.deleter IS NULL')
            ->andWhere('l.deletionDate IS NULL')
            ->andWhere('i.draft = false')
            ->andWhere('i.deleterId IS NULL')
            ->andWhere('i.deletionDate IS NULL')
            ->setParameter('types', $types);
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
