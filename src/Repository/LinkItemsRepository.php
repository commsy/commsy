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

use App\Entity\LinkItems;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the `link_items` table, the untyped join between any two items — among
 * them the link that carries an entry's categories.
 *
 * The table is polymorphic: each side names both an id and the kind of item it
 * points at, which is why this cannot be a Doctrine association. A pair is
 * stored in whichever order it was created, so a lookup asks for the item on
 * either side and constrains the *other* side's type
 * (see cs_link_manager::_performQuery()).
 *
 * Unlike `links`, rows here carry no version.
 *
 * Deletion follows legacy: a row counts only while both `deleter_id` and
 * `deletion_date` are empty.
 *
 * @extends ServiceEntityRepository<LinkItems>
 */
class LinkItemsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LinkItems::class);
    }

    /**
     * Ids of the items of `$linkedItemType` linked to `$itemId`.
     *
     * @param int|null $contextId restricts to one room. Legacy leaves this open
     *                            for most rubric entries, so a link recorded
     *                            under another room would come back too; an
     *                            export of one room passes its id and keeps to
     *                            its own content
     *
     * @return int[] each linked item id once, in no particular order
     */
    public function findLinkedItemIds(int $itemId, string $linkedItemType, ?int $contextId = null): array
    {
        $firstSide = $this->linkedIds('first', 'second', $itemId, $linkedItemType, $contextId);
        $secondSide = $this->linkedIds('second', 'first', $itemId, $linkedItemType, $contextId);

        return array_values(array_unique([...$firstSide, ...$secondSide]));
    }

    /**
     * One direction of the lookup: `$ownSide` carries the item, `$otherSide` the
     * id we are after and the type we constrain.
     *
     * @return int[]
     */
    private function linkedIds(
        string $ownSide,
        string $otherSide,
        int $itemId,
        string $linkedItemType,
        ?int $contextId,
    ): array {
        $builder = $this->createQueryBuilder('li')
            ->select(sprintf('li.%sItemId', $otherSide))
            ->andWhere(sprintf('li.%sItemId = :itemId', $ownSide))
            ->andWhere(sprintf('li.%sItemType = :linkedItemType', $otherSide))
            ->andWhere('li.deleterId IS NULL')
            ->andWhere('li.deletionDate IS NULL')
            ->setParameter('itemId', $itemId)
            ->setParameter('linkedItemType', $linkedItemType);

        if (null !== $contextId) {
            $builder->andWhere('li.contextId = :contextId')
                ->setParameter('contextId', $contextId);
        }

        return array_map(
            static fn (array $row): int => (int) reset($row),
            $builder->getQuery()->getScalarResult()
        );
    }
}
