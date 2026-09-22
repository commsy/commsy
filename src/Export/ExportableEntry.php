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

namespace App\Export;

/**
 * An entry that can stand in an export.
 *
 * What every rubric entry has in common is an id and the room it belongs to,
 * and that is all the export needs to find what hangs off it: its categories,
 * its hashtags, its attachments. The fields of the entry itself come from its
 * `export` serialization group, not from here.
 */
interface ExportableEntry
{
    /**
     * The version of a rubric that keeps none. Only material and section keep
     * versions at all; for everything else legacy writes a zero, and
     * {@see \cs_item::getVersionID()} answers the same.
     */
    public const UNVERSIONED = 0;

    public function getItemId(): int;

    /**
     * The version this entry is exported at, or {@see self::UNVERSIONED}.
     *
     * Links belong to a version: an entry that loses an attachment or a hashtag
     * keeps the old link on the old version. Saying which version is exported is
     * therefore what keeps the export to what the entry shows today.
     */
    public function getVersionId(): int;

    /**
     * The room the entry belongs to.
     *
     * Named after what it is rather than after the column it comes from:
     * `context_id` is a room for a rubric entry, but not everywhere in CommSy —
     * for a user it can be a portal. An entry always sits in exactly one room,
     * which is why this is not nullable.
     */
    public function getRoomId(): int;
}
