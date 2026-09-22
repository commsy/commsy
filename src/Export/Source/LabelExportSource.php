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

use App\Entity\Room;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Repository\LabelRepository;
use App\Rubric\RubricType;

/**
 * The room's labels: its topics, its groups, its institutions.
 *
 * One rubric holding several kinds of entry, which is why each says in `type`
 * what it is. Hashtags are labels too, but they stand on the room as its
 * vocabulary rather than here.
 */
final readonly class LabelExportSource implements RubricExportSource
{
    public function __construct(
        private LabelRepository $labels,
        private AnnotationSubEntries $annotations,
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Label;
    }

    public function entriesFor(Room $room): iterable
    {
        return $this->labels->findForExport($room->getItemId());
    }

    public function subEntriesOf(ExportableEntry $entry): array
    {
        return $this->annotations->of($entry);
    }
}
