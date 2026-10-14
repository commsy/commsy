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

namespace App\Export\Source;

use App\Entity\Dates;
use App\Entity\Room;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Repository\DatesRepository;
use App\Rubric\RubricType;

/**
 * The room's dates.
 *
 * A recurring date is not one entry with a rule but one entry per occurrence,
 * which is how the room shows it and how the export carries it; they name their
 * common recurrence so an importer can put them back together.
 */
final readonly class DateExportSource implements RubricExportSource
{
    public function __construct(
        private DatesRepository $dates,
        private AnnotationSubEntries $annotations,
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Date;
    }

    public function entryClass(): string
    {
        return Dates::class;
    }

    public function entriesFor(Room $room): iterable
    {
        return $this->dates->findForExport($room->getItemId());
    }

    public function find(int $itemId): ?ExportableEntry
    {
        return $this->dates->findOneForExport($itemId);
    }

    public function subEntriesOf(ExportableEntry $entry): array
    {
        return $this->annotations->of($entry);
    }
}
