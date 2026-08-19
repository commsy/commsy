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

namespace Tests\Unit\Entity;

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationAction;
use App\Enum\NotificationType;
use App\Notification\NotificationPayload;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function testConstructorExposesAllData(): void
    {
        $recipient = $this->createMock(Account::class);
        $createdAt = new \DateTimeImmutable('2026-06-01 10:00:00');

        $notification = new Notification(
            $recipient,
            NotificationType::Entry,
            42,
            'New material title',
            'Project room',
            $createdAt,
            1234,
            'material',
            'Ada Lovelace',
        );

        self::assertSame($recipient, $notification->getRecipient());
        self::assertSame(NotificationType::Entry, $notification->getType());
        self::assertSame(42, $notification->getContextId());
        self::assertSame('New material title', $notification->getTitle());
        self::assertSame('Project room', $notification->getRoomTitle());
        self::assertSame($createdAt, $notification->getCreatedAt());
        self::assertSame(1234, $notification->getSourceItemId());
        self::assertSame('material', $notification->getSourceItemType());
        self::assertSame('Ada Lovelace', $notification->getActorName());
    }

    public function testDefaultsToCreatedActionWithEmptyPayload(): void
    {
        $notification = $this->newNotification();

        self::assertSame(NotificationAction::Created, $notification->getAction());
        self::assertTrue($notification->getPayload()->isEmpty());
    }

    public function testCarriesEditedActionAndPayload(): void
    {
        $notification = new Notification(
            $this->createMock(Account::class),
            NotificationType::Entry,
            1,
            'Title',
            'Room',
            new \DateTimeImmutable('2026-06-01 10:00:00'),
            10,
            'date',
            'Editor',
            NotificationAction::Edited,
            new NotificationPayload(place: 'Room 7', hasAttachments: true),
        );

        self::assertSame(NotificationAction::Edited, $notification->getAction());
        self::assertSame('Room 7', $notification->getPayload()->place);
        self::assertTrue($notification->getPayload()->hasAttachments);
    }

    public function testIsUnreadByDefault(): void
    {
        $notification = $this->newNotification();

        self::assertTrue($notification->isUnread());
        self::assertNull($notification->getReadAt());
    }

    public function testMarkReadSetsTimestampAndClearsUnread(): void
    {
        $notification = $this->newNotification();
        $readAt = new \DateTimeImmutable('2026-06-02 09:00:00');

        $notification->markRead($readAt);

        self::assertFalse($notification->isUnread());
        self::assertSame($readAt, $notification->getReadAt());
    }

    public function testMarkReadIsIdempotentAndKeepsFirstTimestamp(): void
    {
        $notification = $this->newNotification();
        $first = new \DateTimeImmutable('2026-06-02 09:00:00');
        $later = new \DateTimeImmutable('2026-06-03 09:00:00');

        $notification->markRead($first);
        $notification->markRead($later);

        self::assertSame($first, $notification->getReadAt(), 'second markRead must not move the marker');
    }

    private function newNotification(): Notification
    {
        return new Notification(
            $this->createMock(Account::class),
            NotificationType::Entry,
            1,
            'Title',
            'Room',
            new \DateTimeImmutable('2026-06-01 10:00:00'),
        );
    }
}
