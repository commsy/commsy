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

namespace App\Tag;

use Doctrine\DBAL\Connection;

/**
 * Soft-delete service for `tag` rows (a.k.a. "categories") and their
 * `tag2tag` tree. Tags are auxiliary — no rubric UI, no index, no
 * attachments — hence this class lives outside {@see \App\Rubric} and is
 * wired in explicitly from {@see \App\Utils\CategoryService}.
 *
 * Hard-delete lives on the aux path (bulk SQL DELETE on cutoff, same
 * shape as `link_items` / `tasks`).
 *
 * Two soft-delete modes: {@see softDelete()} (recursive, used by
 * `removeTag`) and {@see softDeleteWithoutChildren()} (non-recursive,
 * used by `combineTags` so children can be re-parented).
 *
 * Each sweep touches four tables per tag id: `tag`, `link_items`,
 * `tag2tag` (both directions), and the `items` twin.
 */
class TagDeleter
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Soft-deletes a tag and its entire descendant subtree.
     */
    public function softDelete(int $tagId, int $deleterId): void
    {
        // Collect child tag ids before soft-deleting the pivot rows that
        // identify them.
        $childIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT to_item_id FROM tag2tag
                WHERE from_item_id = :tagId
                  AND deletion_date IS NULL',
            ['tagId' => $tagId]
        ));

        $this->sweepSingleTag($tagId, $deleterId);

        foreach ($childIds as $childId) {
            $this->softDelete($childId, $deleterId);
        }
    }

    /**
     * Soft-deletes a single tag without recursing into children (used by
     * `combineTags` so children can be re-parented).
     */
    public function softDeleteWithoutChildren(int $tagId, int $deleterId): void
    {
        $this->sweepSingleTag($tagId, $deleterId);
    }

    /**
     * The four-table sweep: tag → link_items → tag2tag (both directions)
     * → items twin.
     */
    private function sweepSingleTag(int $tagId, int $deleterId): void
    {
        $this->connection->executeStatement(
            'UPDATE tag SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :tagId',
            ['deleterId' => $deleterId, 'tagId' => $tagId]
        );

        $this->connection->executeStatement(
            'UPDATE link_items
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE first_item_id = :tagId OR second_item_id = :tagId',
            ['deleterId' => $deleterId, 'tagId' => $tagId]
        );

        $this->connection->executeStatement(
            'UPDATE tag2tag
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE from_item_id = :tagId OR to_item_id = :tagId',
            ['deleterId' => $deleterId, 'tagId' => $tagId]
        );

        $this->connection->executeStatement(
            'UPDATE items SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :tagId',
            ['deleterId' => $deleterId, 'tagId' => $tagId]
        );
    }
}
