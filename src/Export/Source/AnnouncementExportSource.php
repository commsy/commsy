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
    ) {
    }

    public function rubric(): RubricType
    {
        return RubricType::Announcement;
    }

    public function entriesFor(Room $room): iterable
    {
        return $this->announcements->findForExport($room->getItemId());
    }
}
