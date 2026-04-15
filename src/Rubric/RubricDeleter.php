<?php

namespace App\Rubric;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.rubric.deleter')]
interface RubricDeleter
{
    public function rubricKey(): string;

    /**
     * Returns item IDs of items where $userId is the creator (not modifier!)
     * in $contextId.
     *
     * @return int[]
     */
    public function findItemIdsCreatedBy(int $userId, int $contextId): array;

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
     * migrated, they must call {@see ItemDeletionHelper::softDeleteItemsRow}
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
}
