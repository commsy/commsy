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
 * `tag2tag` tree structure.
 *
 * Tags are **not a rubric** — they carry no main-content UI of their own,
 * no attachments, no annotations, no Elasticsearch index. They are a
 * hierarchical classification structure (`tag` table + `tag2tag` pivot)
 * attached to rubric items via `link_items`. That is why this class
 * deliberately lives outside {@see \App\Rubric} and does **not** implement
 * {@see \App\Rubric\RubricDeleter}:
 *
 *  - No `app.rubric.deleter` tag — {@see \App\Rubric\RubricHardDeleter}
 *    and {@see \App\Rubric\UserContentDeleter} iterate only rubric-primary
 *    types. Tags get wired in explicitly at their call sites in
 *    {@see \App\Utils\CategoryService}.
 *  - No `rubricType()` — there is no `RubricType` enum case for tags (by
 *    design; `LabelDeleter` covers the visually-similar rubric-scoped
 *    labels, which is a different table and a different concept).
 *  - No `ItemDeletedEvent` dispatch — tags are not indexed, do not trigger
 *    mail notifications, carry no etherpad / file-storage side effects.
 *  - No hard-delete path on this class — `hardDeleteOlderThan()` lives on
 *    {@see \App\Legacy\LegacyAuxHardDeleter::hardDeleteTagRows()} and
 *    {@see \App\Legacy\LegacyAuxHardDeleter::hardDeleteTag2TagPivotRows()},
 *    because both tables are auxiliary in shape (bulk SQL DELETE on
 *    cutoff, identical to `link_items` / `tasks`). Splitting the sweep
 *    between here and the aux repo would duplicate a single cutoff query
 *    across two owners.
 *
 * Legacy parity: `cs_tag_manager::delete($id, $recursive = true)` was
 * inlined into {@see \App\Utils\CategoryService::removeTag} +
 * {@see \App\Utils\CategoryService::combineTags} during #5082 (commit
 * `14ccf6283`) to unblock the legacy-manager removal. This class lifts
 * that inline SQL back out of the service so the cleanup has a single
 * owner and `CategoryService` stays a thin wrapper over business logic
 * (addTag / updateTag / combineTags orchestration).
 *
 * Two soft-delete modes are exposed, matching the two legacy call
 * shapes:
 *
 *  - {@see softDelete()}: recursive — mirrors `tag_manager->delete($id)`
 *    (the default, whole-subtree delete). Used by `removeTag`.
 *  - {@see softDeleteWithoutChildren()}: non-recursive — mirrors
 *    `tag_manager->delete($id, false)`. Used by `combineTags`, where the
 *    children need to survive so they can be re-parented under the merged
 *    tag.
 *
 * Both modes sweep four tables per tag id:
 *
 *   1. `tag`        — the row itself (deletion_date / deleter_id).
 *   2. `link_items` — every row where this tag is either first or second
 *                     member of a classification link.
 *   3. `tag2tag`    — pivot rows in both directions (parent-of + child-of).
 *   4. `items`      — the shared twin row (legacy `parent::delete()`).
 *
 * The recursive variant additionally walks into every `to_item_id` from
 * the soft-deleted `tag2tag` rows and applies the same four-table sweep
 * there. Children are collected **before** step 3 so that the pivot
 * table's post-delete state does not hide the subtree.
 */
class TagDeleter
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Soft-deletes a tag and its entire descendant subtree.
     *
     * Mirrors `cs_tag_manager::delete($id)` with the recursive cascade
     * implicit in `cs_tag2tag_manager::deleteTagLinksForTag()`.
     */
    public function softDelete(int $tagId, int $deleterId): void
    {
        // Collect child tag ids before we soft-delete the pivot rows that
        // identify them — the deletion_date filter in the lookup query
        // protects us from revisiting already-soft-deleted subtrees.
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
     * Soft-deletes a single tag **without** recursing into children.
     *
     * Mirrors `cs_tag_manager::delete($id, false)`, the path
     * `cs_tag2tag_manager::combine()` used so the merged tag's children
     * could survive and be re-parented.
     */
    public function softDeleteWithoutChildren(int $tagId, int $deleterId): void
    {
        $this->sweepSingleTag($tagId, $deleterId);
    }

    /**
     * The four-table sweep applied to a single tag id by both soft-delete
     * modes:
     *
     *   tag → link_items → tag2tag (both directions) → items twin.
     *
     * Order matters only for readability — every statement is an
     * independent UPDATE, and all four must land for legacy parity.
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
