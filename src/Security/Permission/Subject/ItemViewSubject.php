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

namespace App\Security\Permission\Subject;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Decoupled view of an item's permission-relevant fields. Construction
 * is the caller's job — the cs_item.maySee wrapper builds it from the
 * legacy item, future per-rubric checkers will build it from their
 * Doctrine entities (Materials, Dates, Announcements, …).
 *
 * Keeping this as a plain value object means {@see \App\Security\Permission\Checker\ItemViewChecker}
 * stays Doctrine-and-legacy-agnostic — it only ever sees the fields it
 * actually needs to decide.
 */
#[Exclude]
final readonly class ItemViewSubject
{
    public function __construct(
        public int $itemId,
        /** Room item_id the item lives in (null for a context that
         *  doesn't resolve — treated as a "deleted context" so canSee
         *  returns false). */
        public ?int $contextId,
        /** user_item id of the creator. Compared against the actor's
         *  membership item id when the entry is deactivated. */
        public ?int $creatorId,
        /** activation_date in the future — only mods or the creator may
         *  see deactivated entries (cs_item::isNotActivated semantics). */
        public bool $isDeactivated,
        /** the item's context (room) is soft-deleted — the maySee path
         *  short-circuits to false in that case. */
        public bool $contextIsDeleted,
        /** Type-specific tombstone marker — `true` when the item's body
         *  has been replaced with placeholder text while the row stays
         *  alive (currently only `cs_discussionarticle_item` with
         *  `public = -2`, to preserve discussion-thread hierarchies).
         *  Treated as "not viewable" regardless of other state, which
         *  matches the legacy `cs_file_item::maySeeLinkedItem` filter. */
        public bool $hasOverwrittenContent = false,
    ) {
    }
}
