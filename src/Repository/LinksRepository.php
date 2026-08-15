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

use App\Entity\Links;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the `links` table, which holds typed links between two items — among
 * them `buzzword_for`, the link that carries an entry's hashtags.
 *
 * A link is undirected in practice: legacy asks for links *from* an item and
 * links *to* it and merges both, so an entry is linked to whatever sits at the
 * other end, whichever column it happens to occupy
 * (see cs_links_manager::getLinks()). This repository does the same and returns
 * the other end.
 *
 * Versions belong to the side they were written on. A material carries its
 * hashtags per version, so asking with a version id matches the version column
 * of the *same* side — mirroring how legacy filters its cached rows.
 *
 * Deletion follows legacy exactly: `deleter_id IS NULL`, and `deletion_date` is
 * deliberately not consulted. Legacy does not, and a reader that disagreed with
 * the writer would show links the interface hides, or hide links it shows.
 *
 * @extends ServiceEntityRepository<Links>
 */
class LinksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Links::class);
    }

    /**
     * Ids of the items linked to `$itemId` by `$linkType` within `$contextId`.
     *
     * @param int|null $versionId version of `$itemId` whose links are wanted;
     *                            null takes them regardless of version
     *
     * @return int[] each linked item id once, in no particular order
     */
    public function findLinkedItemIds(
        int $itemId,
        string $linkType,
        int $contextId,
        ?int $versionId = null,
    ): array {
        $fromSide = $this->linkedIds('from', 'to', $itemId, $linkType, $contextId, $versionId);
        $toSide = $this->linkedIds('to', 'from', $itemId, $linkType, $contextId, $versionId);

        return array_values(array_unique([...$fromSide, ...$toSide]));
    }

    /**
     * One direction of the lookup: `$ownSide` carries the item, `$otherSide` the
     * id we are after.
     *
     * @return int[]
     */
    private function linkedIds(
        string $ownSide,
        string $otherSide,
        int $itemId,
        string $linkType,
        int $contextId,
        ?int $versionId,
    ): array {
        $builder = $this->createQueryBuilder('l')
            ->select(sprintf('l.%sItemId', $otherSide))
            ->andWhere('l.linkType = :linkType')
            ->andWhere('l.contextId = :contextId')
            ->andWhere('l.deleterId IS NULL')
            ->andWhere(sprintf('l.%sItemId = :itemId', $ownSide))
            ->setParameter('linkType', $linkType)
            ->setParameter('contextId', $contextId)
            ->setParameter('itemId', $itemId);

        if (null !== $versionId) {
            $builder->andWhere(sprintf('l.%sVersionId = :versionId', $ownSide))
                ->setParameter('versionId', $versionId);
        }

        return array_map(
            static fn (array $row): int => (int) reset($row),
            $builder->getQuery()->getScalarResult()
        );
    }
}
