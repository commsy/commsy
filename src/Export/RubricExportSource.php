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

use App\Entity\Room;
use App\Rubric\RubricType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Hands the export the entries of exactly one rubric.
 *
 * A rubric joins the export by being here; the document builder knows none of
 * them by name. What belongs in an export — no deleted entries, no drafts — is
 * each source's own business, because only it knows how its rubric stores that.
 */
#[AutoconfigureTag('app.export.rubric_source')]
interface RubricExportSource
{
    /**
     * The rubric these entries belong to. It names the section in the document,
     * so the document speaks the same vocabulary as `items.type` instead of an
     * invented one.
     */
    public function rubric(): RubricType;

    /**
     * @return iterable<ExportableEntry> in a fixed order, so two exports of an
     *                                   unchanged room can be compared
     */
    public function entriesFor(Room $room): iterable;

    /**
     * The entries that hang under this one — a material's sections, a comment
     * on an entry — keyed by the name they stand under inside it.
     *
     * Sub-entries do not stand on their own in the document; they belong to
     * their parent and go no deeper than one level.
     *
     * @return array<string, list<ExportableEntry>> empty for a rubric whose
     *                                              entries carry nothing
     */
    public function subEntriesOf(ExportableEntry $entry): array;
}
