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

use App\Entity\Discussions;
use App\Entity\Room;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Repository\DiscussionsRepository;
use App\Repository\DiscussionarticlesRepository;
use App\Rubric\RubricType;

/**
 * The room's discussions.
 */
final readonly class DiscussionExportSource implements RubricExportSource
{
    public function __construct(
        private DiscussionsRepository $discussions,
        private DiscussionarticlesRepository $articles,
        private AnnotationSubEntries $annotations,
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Discussion;
    }

    public function entriesFor(Room $room): iterable
    {
        return $this->discussions->findForExport($room->getItemId());
    }

    /**
     * The articles hang under their discussion and stay flat: the thread is a
     * path spelled out in each article's position, not a nesting of its own.
     */
    public function subEntriesOf(ExportableEntry $entry): array
    {
        if (!$entry instanceof Discussions) {
            return [];
        }

        return $this->annotations->of($entry)
            + ['articles' => $this->articles->findForExport($entry->getItemId())];
    }
}
