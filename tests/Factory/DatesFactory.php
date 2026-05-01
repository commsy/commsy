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
            // start_day, datetime_start, datetime_end are NOT NULL in the schema.
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

                // Legacy _newDate() falls back to getDefaultCalendarId(), which
                // dereferences a creator item the test fixture's legacy room does
                // not populate — short-circuit by setting a default calendar explicitly.
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

                // Promote legacy trigger_error into a real failure so swallowed
                // DBAL warnings (missing NOT-NULL columns, etc.) break the test.
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
