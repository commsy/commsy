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

namespace App\Room\Vocabulary;

use App\Entity\Calendars;
use App\Entity\Room;
use App\Repository\CalendarsRepository;
use App\Serializer\TitleCollator;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Calendars are a table of their own, ordered by title.
 */
#[AsTaggedItem(priority: 30)]
final readonly class CalendarVocabulary implements VocabularyKind
{
    public function __construct(
        private CalendarsRepository $calendars,
        private TitleCollator $titles,
    ) {
    }

    #[Override]
    public function field(): string
    {
        return 'calendars';
    }

    #[Override]
    public function termClass(): string
    {
        return RoomCalendar::class;
    }

    #[Override]
    public function description(): string
    {
        return 'The room\'s calendars; dates point at them by calendarId.';
    }

    /**
     * @return list<RoomCalendar>
     */
    #[Override]
    public function of(Room $room): array
    {
        $calendars = array_map(
            static fn (Calendars $calendar): RoomCalendar => new RoomCalendar(
                (int) $calendar->getId(),
                (string) $calendar->getTitle(),
                $calendar->getColor(),
                $calendar->getDefaultCalendar(),
            ),
            $this->calendars->findByRoomId($room->getItemId())
        );

        usort($calendars, fn (RoomCalendar $a, RoomCalendar $b): int => $this->titles->compare($a->title, $b->title));

        return $calendars;
    }
}
