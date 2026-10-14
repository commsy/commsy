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

use App\Entity\Discussionarticles;
use App\Entity\Discussions;
use App\Entity\Room;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Export\SubEntryType;
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

    public function entryClass(): string
    {
        return Discussions::class;
    }

    public function entriesFor(Room $room, int $offset = 0, ?int $limit = null): iterable
    {
        return $this->discussions->findForExport($room->getItemId(), $offset, $limit);
    }

    public function countFor(Room $room): int
    {
        return $this->discussions->countForExport($room->getItemId());
    }

    public function find(int $itemId): ?ExportableEntry
    {
        return $this->discussions->findOneForExport($itemId);
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

    public function subEntryTypes(): array
    {
        return $this->annotations->types()
            + ['articles' => new SubEntryType(
                Discussionarticles::class,
                'The articles of the discussion, ordered by position, which is the order of the conversation.'
            )];
    }
}
