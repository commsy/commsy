<?php

namespace App\Rubric;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.rubric.deleter')]
interface RubricDeleter
{
    /**
     * The rubric type this deleter is responsible for. Used by
     * {@see \App\Action\Delete\DeleteGeneric} to dispatch on
     * `cs_item::getItemType()`.
     */
    public function rubricType(): RubricType;

    /**
     * Returns item IDs of items where $userId is the creator (not modifier!)
     * in $contextId.
     *
     * @return int[]
     */
    public function findItemIdsCreatedBy(int $userId, int $contextId): array;

    /**
     * Returns item IDs of all still-alive top-level items of this rubric in
     * $contextId. Sub-entries (sections, steps, discussion articles) are NOT
     * returned — they are cleaned up transitively by their parent's
     * {@see softDeleteItem()} call.
     *
     * @return int[]
     */
    public function findItemIdsInContext(int $contextId): array;

    /**
     * Deletes a single item of this rubric in a self-contained way.
     *
     * Implementations handle ALL cleanup for one item: the rubric-specific
     * table, rubric-owned sub-entries, links, annotations, file links, the
     * shared `items` row, and `ItemDeletedEvent` dispatch.
     *
     * @param int $itemId    the id of the item to delete
     * @param int $deleterId the id of the user performing the deletion
     */
    public function softDeleteItem(int $itemId, int $deleterId): void;

    /**
     * NULLifies creator_id/modifier_id references to $userId in items of
     * this rubric within $contextId.
     */
    public function nullifyReferencesInContext(int $userId, int $contextId): void;

    /**
     * Physically removes all rows in this rubric's own table(s) whose
     * `deletion_date` is older than $days. The `items`-twin row is NOT
     * touched here — it falls out as part of the orchestrator's common
     * items sweep.
     *
     * Implementations also cover sub-entry tables owned by this rubric
     * (e.g. MaterialDeleter sweeps `section`, TodoDeleter sweeps `step`).
     *
     * Returns the number of rows physically removed.
     */
    public function hardDeleteOlderThan(int $days): int;
}
