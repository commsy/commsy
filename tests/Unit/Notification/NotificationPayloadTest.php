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

use App\Notification\NotificationPayload;
use PHPUnit\Framework\TestCase;

class NotificationPayloadTest extends TestCase
{
    public function testEmptyPayloadSerialisesToEmptyArray(): void
    {
        $payload = new NotificationPayload();

        self::assertTrue($payload->isEmpty());
        self::assertSame([], $payload->toArray());
    }

    public function testToArrayKeepsOnlySetValues(): void
    {
        $payload = new NotificationPayload(creatorName: 'Ada Lovelace', hasAttachments: true);

        self::assertSame(
            ['creatorName' => 'Ada Lovelace', 'hasAttachments' => true],
            $payload->toArray(),
        );
    }

    public function testRoundTripsThroughArray(): void
    {
        $payload = new NotificationPayload(
            creatorName: 'Ada Lovelace',
            place: 'Room 7',
            dateStart: '2026-06-20T09:00:00+00:00',
            dateEnd: '2026-06-20T10:00:00+00:00',
            wholeDay: false,
            materialAuthor: 'Grace Hopper',
            publishingDate: '2026-06-01',
            hasAttachments: true,
        );

        $restored = NotificationPayload::fromArray($payload->toArray());

        self::assertEquals($payload, $restored);
    }

    public function testFromArrayToleratesMissingKeys(): void
    {
        $payload = NotificationPayload::fromArray(['place' => 'Room 7']);

        self::assertSame('Room 7', $payload->place);
        self::assertNull($payload->creatorName);
        self::assertFalse($payload->hasAttachments);
    }
}
