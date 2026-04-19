<?php

namespace App\Rubric;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.rubric.deleter')]
interface RubricDeleter
{
    /**
     * The rubric type this deleter is responsible for. Used by the
     * dispatching code in {@see \App\Action\Delete\DeleteGeneric} (and
     * friends) to pick the right implementation based on
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
     * $contextId (i.e. rows in the rubric's primary table with
     * `deleter_id IS NULL AND deletion_date IS NULL`). Sub-entries (sections,
     * steps, discussion articles) are NOT returned here — they are cleaned up
     * transitively by their parent's {@see deleteItem()} call.
     *
     * Designed for the {@see \App\Rubric\Room\RoomContentDeleter} orchestrator
     * which iterates an entire room's content. Symmetric to
     * {@see findItemIdsCreatedBy()}; if room sizes ever make the full-array
     * return a memory concern we can switch to keyset pagination at that point.
     *
     * @return int[]
     */
    public function findItemIdsInContext(int $contextId): array;

    /**
     * Deletes a single item of this rubric in a self-contained way.
     *
     * Implementations are responsible for ALL cleanup associated with deleting
     * one item of this rubric: the rubric-specific table, rubric-owned sub-entries
     * (Sections, DiscussionArticles, Steps, …), links, annotations, file links,
     * the shared `items` row, and `ItemDeletedEvent` dispatch (which triggers
     * ES removal, mail notifications, etc.).
     *
     * This makes `deleteItem()` the single source of truth for "how is an item
     * of this rubric deleted". Both the UI delete action
     * ({@see \App\Action\Delete\DeleteAction}) and the user-footprint erasure
     * flow ({@see UserContentDeleter}) invoke this method without adding any
     * further deletion steps.
     *
     * Deleters still delegating to the legacy `cs_item::delete()` cascade get
     * the items-row cleanup transitively via the legacy base manager; once
     * migrated, they must call {@see RubricDeletionHelper::softDeleteItemsRow}
     * explicitly.
     *
     * @param int $itemId    the id of the item to delete
     * @param int $deleterId the id of the user performing the deletion
     */
    public function deleteItem(int $itemId, int $deleterId): void;

    /**
     * NULLifies creator_id/modifier_id references to $userId
     * in items of this rubric within $contextId.
     */
    public function nullifyReferencesInContext(int $userId, int $contextId): void;

    /**
     * Physically removes all rows in this rubric's own table(s) whose
     * `deletion_date` is older than $days. Mirrors the legacy
     * `cs_*_manager::deleteReallyOlderThan()` semantics: the `items`-twin
     * row is NOT touched here — it falls out as part of the orchestrator's
     * common items sweep (see {@see \App\Rubric\RubricHardDeleter}).
     *
     * Implementations also cover sub-entry tables owned by this rubric
     * (e.g. MaterialDeleter sweeps `section`, TodoDeleter sweeps `step`,
     * DiscussionDeleter sweeps `discarticle`).
     *
     * Called from {@see \App\Cron\Tasks\CronHardDelete} once the soft-delete
     * grace period has elapsed. Returns the number of rows physically
     * removed (for logging / test assertions).
     */
    public function hardDeleteOlderThan(int $days): int;
}
