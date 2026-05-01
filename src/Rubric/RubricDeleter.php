<?php

namespace App\Rubric;

use cs_item;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.rubric.deleter')]
interface RubricDeleter
{
    public function rubricKey(): string;

    /**
     * Returns items where $userId is the creator (not modifier!) in $contextId.
     *
     * @return iterable<cs_item>
     */
    public function findItemsCreatedBy(int $userId, int $contextId): iterable;

    /**
     * Deletes a single item of this rubric.
     *
     * Currently delegates to the legacy cs_item::delete() which handles
     * cascade deletion of sub-items (Sections, DiscussionArticles, Steps),
     * Elasticsearch removal, link cleanup, etc.
     */
    public function deleteItem(cs_item $item): void;

    /**
     * NULLifies creator_id/modifier_id references to $userId
     * in items of this rubric within $contextId.
     */
    public function nullifyReferencesInContext(int $userId, int $contextId): void;
}
