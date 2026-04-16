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

namespace Tests\Factory;

use App\Entity\Calendars;
use App\Entity\Room;
use App\Entity\User;
use LogicException;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Calendars>
 *
 * Creates a calendar row attached to a given room (and optionally a creator).
 * Calendars is a plain ORM entity — no legacy manager is involved, so Foundry's
 * default persistence applies.
 *
 * Required inputs:
 *  - `room`     App\Entity\Room — the containing context; `context_id` is taken from `$room->getItemId()`
 *
 * Optional inputs:
 *  - `creator`  App\Entity\User — the creator; `creator_id` is taken from `$creator->getItemId()`
 */
final class CalendarsFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Calendars::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->words(2, true),
            'color' => '#' . self::faker()->regexify('[0-9a-f]{6}'),
            'defaultCalendar' => false,
            'synctoken' => 0,
            'room' => null,
            'creator' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->instantiateWith(Instantiator::withConstructor()->allowExtra(
                'room',
                'creator',
            ))
            ->afterInstantiate(function (Calendars $calendar, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('CalendarsFactory requires "room" (App\Entity\Room).');
                }

                $calendar->setContextId($room->getItemId());

                if ($creator instanceof User) {
                    $calendar->setCreatorId($creator->getItemId());
                }
            });
    }
}
