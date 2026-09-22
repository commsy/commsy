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

use App\Entity\Tag;
use App\Entity\Tag2tag;
use App\Serializer\TitleCollator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads a room's categories, which are `tags` arranged into a tree by `tag2tag`
 * (from_item_id = parent, to_item_id = child).
 *
 * Every room has one technical root carrying {@see self::ROOT_TITLE}; it is the
 * anchor, not a category, and stays out of the result.
 *
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    private const ROOT_TITLE = 'CS_TAG_ROOT';

    public function __construct(
        ManagerRegistry $registry,
        private readonly TitleCollator $titles,
    ) {
        parent::__construct($registry, Tag::class);
    }

    public function findRootFor(int $contextId): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.contextId = :contextId')
            ->andWhere('t.title = :title')
            ->andWhere('t.deletionDate IS NULL')
            ->andWhere('t.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->setParameter('title', self::ROOT_TITLE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The room's categories as a tree, ordered by title at every level.
     *
     * Legacy leaves the order of a level undefined, so it is fixed here: an
     * export that reshuffles between runs cannot be compared.
     *
     * @see TitleCollator for why this is not a byte comparison
     *
     * @return list<array{id: int, title: string, children: list<array<string, mixed>>}>
     */
    public function findCategoryTreeFor(int $contextId): array
    {
        $root = $this->findRootFor($contextId);
        if (null === $root) {
            return [];
        }

        return $this->buildBranch(
            (int) $root->getItemId(),
            $this->titlesFor($contextId),
            $this->childIdsFor($contextId),
            []
        );
    }

    /**
     * @param array<int, string>     $titles
     * @param array<int, list<int>>  $childIds
     * @param list<int>              $seen     guards against a cycle: `tag2tag`
     *                                         is a net by construction, and one
     *                                         loop would recurse for ever
     *
     * @return list<array{id: int, title: string, children: list<array<string, mixed>>}>
     */
    private function buildBranch(int $parentId, array $titles, array $childIds, array $seen): array
    {
        $branch = [];
        $seen[] = $parentId;

        foreach ($childIds[$parentId] ?? [] as $childId) {
            if (!isset($titles[$childId]) || in_array($childId, $seen, true)) {
                continue;
            }

            $branch[] = [
                'id' => $childId,
                'title' => $titles[$childId],
                'children' => $this->buildBranch($childId, $titles, $childIds, $seen),
            ];
        }

        usort($branch, fn (array $a, array $b): int => $this->titles->compare($a['title'], $b['title']));

        return $branch;
    }

    /**
     * @return array<int, string> title by tag id
     */
    private function titlesFor(int $contextId): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.itemId', 't.title')
            ->andWhere('t.contextId = :contextId')
            ->andWhere('t.deletionDate IS NULL')
            ->andWhere('t.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'title', 'itemId');
    }

    /**
     * @return array<int, list<int>> child ids by parent id
     */
    private function childIdsFor(int $contextId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('l.fromItemId', 'l.toItemId')
            ->from(Tag2tag::class, 'l')
            ->andWhere('l.contextId = :contextId')
            ->andWhere('l.deletionDate IS NULL')
            ->andWhere('l.deleter IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getScalarResult();

        $childIds = [];
        foreach ($rows as $row) {
            $childIds[(int) $row['fromItemId']][] = (int) $row['toItemId'];
        }

        return $childIds;
    }
}
