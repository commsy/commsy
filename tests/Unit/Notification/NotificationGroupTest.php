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

namespace Tests\Unit\Notification;

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationAction;
use App\Enum\NotificationType;
use App\Notification\NotificationGroup;
use PHPUnit\Framework\TestCase;

class NotificationGroupTest extends TestCase
{
    public function testOriginPrefersCreationAndLatestIsNewest(): void
    {
        $created = $this->event(NotificationAction::Created, read: false, at: '2026-06-01 10:00:00');
        $edited = $this->event(NotificationAction::Edited, read: false, at: '2026-06-02 10:00:00');

        $group = new NotificationGroup([$created, $edited]);

        self::assertSame($created, $group->origin());
        self::assertSame($edited, $group->latest());
        self::assertTrue($group->isUnread());
    }

    public function testIndicatorIsNewWhenTheCreationIsUnread(): void
    {
        $group = new NotificationGroup([
            $this->event(NotificationAction::Created, read: false, at: '2026-06-01 10:00:00'),
            $this->event(NotificationAction::Edited, read: true, at: '2026-06-02 10:00:00'),
        ]);

        self::assertSame('new', $group->indicatorStatus());
    }

    public function testIndicatorIsChangedWhenOnlyLaterEventsAreUnread(): void
    {
        $group = new NotificationGroup([
            $this->event(NotificationAction::Created, read: true, at: '2026-06-01 10:00:00'),
            $this->event(NotificationAction::Edited, read: false, at: '2026-06-02 10:00:00'),
        ]);

        self::assertSame('changed', $group->indicatorStatus());
    }

    public function testIndicatorIsNullWhenEverythingIsRead(): void
    {
        $group = new NotificationGroup([
            $this->event(NotificationAction::Created, read: true, at: '2026-06-01 10:00:00'),
            $this->event(NotificationAction::Edited, read: true, at: '2026-06-02 10:00:00'),
        ]);

        self::assertNull($group->indicatorStatus());
        self::assertFalse($group->isUnread());
    }

    public function testOriginFallsBackToOldestWhenNoCreationEvent(): void
    {
        $olderEdit = $this->event(NotificationAction::Edited, read: false, at: '2026-06-01 10:00:00');
        $newerEdit = $this->event(NotificationAction::Edited, read: false, at: '2026-06-02 10:00:00');

        $group = new NotificationGroup([$olderEdit, $newerEdit]);

        self::assertSame($olderEdit, $group->origin());
    }

    private function event(NotificationAction $action, bool $read, string $at): Notification
    {
        $notification = new Notification(
            $this->createMock(Account::class),
            NotificationType::Entry,
            5,
            'Title',
            'Room',
            new \DateTimeImmutable($at),
            42,
            'material',
            'Actor',
            $action,
        );

        if ($read) {
            $notification->markRead(new \DateTimeImmutable('2026-07-01 00:00:00'));
        }

        return $notification;
    }
}
