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
use App\Entity\Dates;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Dates>
 *
 * Creates a dates item through the **legacy** manager chain
 * (`cs_dates_manager::getNewItem()` + `save()`) — the same path controllers
 * take when a user creates an appointment. Keeps test fixtures faithful to
 * production so the deletion tests exercise the real schema state.
 *
 * Required inputs:
 *  - `room`    App\Entity\Room — the containing context
 *  - `creator` App\Entity\User — priming the legacy current-user slot
 *
 * Optional inputs (for series tests):
 *  - `recurrenceId`      int    — share a value across instances to form a series
 *  - `recurrencePattern` array  — RRULE-like pattern (stored serialized by legacy)
 */
final class DatesFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Dates::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(4),
            'description' => self::faker()->paragraph(),
            'room' => null,
            'creator' => null,
            // `start_day`, `datetime_start`, `datetime_end` are NOT NULL in
            // the dates schema — provide a sensible default so callers only
            // override them for tests that actually care about the values.
            'startDay' => date('Y-m-d'),
            'startTime' => '10:00:00',
            'endDay' => date('Y-m-d'),
            'endTime' => '11:00:00',
            'datetimeStart' => date('Y-m-d') . ' 10:00:00',
            'datetimeEnd' => date('Y-m-d') . ' 11:00:00',
            'recurrenceId' => null,
            'recurrencePattern' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra(
                'room',
                'creator',
                'startDay',
                'startTime',
                'endDay',
                'endTime',
                'datetimeStart',
                'datetimeEnd',
                'recurrenceId',
                'recurrencePattern',
            ))
            ->afterInstantiate(function(Dates $dates, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('DatesFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('DatesFactory requires "creator" (App\Entity\User).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                // Legacy _newDate() falls back to `$contextItem->getDefaultCalendarId()`
                // if no calendarId is set on the item, which in turn calls
                // CalendarsService::createCalendar() and dereferences
                // `$roomItem->getCreatorItem()` — but in the test fixture the
                // legacy room has no creator populated. Short-circuit by
                // making sure a default calendar exists and set it explicitly.
                $calendarId = $this->ensureDefaultCalendar($room, $creator);

                $item = $env->getDatesManager()->getNewItem();
                $item->setCalendarId($calendarId);
                $item->setTitle($dates->getTitle());
                if ($dates->getDescription() !== null) {
                    $item->setDescription($dates->getDescription());
                }
                $item->setContextID($room->getItemId());
                $item->setStartingDay($attributes['startDay']);
                $item->setStartingTime($attributes['startTime']);
                $item->setEndingDay($attributes['endDay']);
                $item->setEndingTime($attributes['endTime']);
                $item->setDateTime_start($attributes['datetimeStart']);
                $item->setDateTime_end($attributes['datetimeEnd']);

                if (!empty($attributes['recurrenceId'])) {
                    $item->setRecurrenceId((string) $attributes['recurrenceId']);
                }
                if (!empty($attributes['recurrencePattern'])) {
                    $item->setRecurrencePattern($attributes['recurrencePattern']);
                }

                // Legacy `_newDate` swallows DBAL exceptions via trigger_error —
                // promote the E_USER_WARNING to a real exception so missing
                // NOT-NULL columns show up as real test failures.
                set_error_handler(function(int $errno, string $errstr): bool {
                    throw new LogicException(sprintf('Legacy save() warning: %s', $errstr));
                }, E_USER_WARNING);
                try {
                    $item->save();
                } finally {
                    restore_error_handler();
                }

                $itemId = $item->getItemID();
                if ($itemId <= 0) {
                    throw new LogicException('Legacy save() did not return a valid item id.');
                }

                $idProperty = new ReflectionProperty(Dates::class, 'itemId');
                $idProperty->setValue($dates, $itemId);
                $dates->setContextId($room->getItemId());
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }

    private function ensureDefaultCalendar(Room $room, User $creator): int
    {
        $repo = $this->entityManager->getRepository(Calendars::class);
        $existing = $repo->findOneBy(['context_id' => $room->getItemId()]);
        if ($existing !== null) {
            return (int) $existing->getId();
        }

        $calendar = new Calendars();
        $calendar->setContextId($room->getItemId());
        $calendar->setCreatorId($creator->getItemId());
        $calendar->setTitle('Standard');
        $calendar->setColor('#ffffff');
        $calendar->setDefaultCalendar(true);
        $calendar->setSynctoken(0);

        $this->entityManager->persist($calendar);
        $this->entityManager->flush();

        return (int) $calendar->getId();
    }
}
