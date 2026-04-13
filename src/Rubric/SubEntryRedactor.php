<?php

namespace App\Rubric;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handles sub-entries (sections, discussion articles, steps, annotations) during
 * user deletion. Sub-entries are never deleted — only their content is redacted
 * (CASCADE) or their references nullified (KEEP/CASCADE).
 *
 * This is distinct from RubricDeleter which handles main entries (materials,
 * discussions, todos, etc.) that can be fully deleted.
 */
#[AutoconfigureTag('app.rubric.sub_entry_redactor')]
interface SubEntryRedactor
{
    /**
     * Redacts content of sub-entries created by $userId in $contextId.
     *
     * Used in CASCADE mode for sub-entries whose parent item belongs to
     * another user (and thus survives the deletion). Sub-entries in deleted
     * parent items are already removed by the legacy cascade.
     */
    public function redactContentOfUser(int $userId, int $contextId): void;

    /**
     * NULLifies creator_id/modifier_id references to $userId in sub-entries
     * within $contextId.
     */
    public function nullifyReferencesInContext(int $userId, int $contextId): void;
}
