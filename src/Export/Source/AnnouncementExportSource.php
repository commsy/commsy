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

use App\Entity\Announcement;
use App\Entity\Room;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Repository\AnnouncementRepository;
use App\Rubric\RubricType;

/**
 * The room's announcements.
 *
 * An announcement that has run out stays in the export: its validity decides
 * what the room shows today, not whether it was ever said.
 */
final readonly class AnnouncementExportSource implements RubricExportSource
{
    public function __construct(
        private AnnouncementRepository $announcements,
        private AnnotationSubEntries $annotations,
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Announcement;
    }

    public function entryClass(): string
    {
        return Announcement::class;
    }

    public function entriesFor(Room $room, int $offset = 0, ?int $limit = null): iterable
    {
        return $this->announcements->findForExport($room->getItemId(), $offset, $limit);
    }

    public function countFor(Room $room): int
    {
        return $this->announcements->countForExport($room->getItemId());
    }

    public function find(int $itemId): ?ExportableEntry
    {
        return $this->announcements->findOneForExport($itemId);
    }

    public function subEntriesOf(ExportableEntry $entry): array
    {
        return $this->annotations->of($entry);
    }
}
