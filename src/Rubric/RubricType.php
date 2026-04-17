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

namespace App\Rubric;

/**
 * Canonical rubric types understood by the deletion pipeline.
 *
 * Values mirror the legacy `cs_*_item::_type` strings (see `CS_*_TYPE`
 * constants in `legacy/etc/cs_constants.php`), so callers that still hand
 * around `$item->getItemType()` results or read `items.type` from the
 * database can map in/out without surprise.
 *
 * Mirrors the design of {@see \App\Room\RoomType}. Only types that are
 * the primary subject of a {@see RubricDeleter} have a case here —
 * sub-entries (Section, Step, DiscussionArticle) are owned by their
 * parent's deleter and carry no case of their own, and auxiliary types
 * (link_item, link, file, task, user) are not rubrics at all.
 */
enum RubricType: string
{
    case Announcement = 'announcement';
    case Annotation = 'annotation';
    case Date = 'date';
    case Discussion = 'discussion';
    /**
     * Covers the complete `cs_label_item` hierarchy (topic / hashtag /
     * buzzword / timepulse / institution / group). The `items.type`
     * column is `'label'` for all of them; the specific subtype is
     * stored in the `labels.type` column and resolved per UI surface.
     */
    case Label = 'label';
    case Material = 'material';
    case Todo = 'todo';

    /**
     * Accepts a legacy type string and returns the matching case, or null
     * if the string does not correspond to a managed rubric type (e.g.
     * `section`, `step`, `discarticle`, `task`, `link_item`, `user`, …).
     */
    public static function tryFromLegacyString(string $type): ?self
    {
        return self::tryFrom($type);
    }
}
