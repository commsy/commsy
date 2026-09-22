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
     * The version an unversioned rubric's links carry. Only material and
     * section keep versions at all; everything else sits at zero, and a
     * versioned rubric will have to say which version it is exported at.
     */
    public const UNVERSIONED = 0;

    public function getItemId(): int;

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
