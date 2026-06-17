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

use App\Notification\NotificationPayloadFactory;
use cs_dates_item;
use cs_item;
use cs_list;
use cs_material_item;
use cs_user_item;
use PHPUnit\Framework\TestCase;

class NotificationPayloadFactoryTest extends TestCase
{
    public function testGenericItemCarriesCreatorAndAttachmentFlagOnly(): void
    {
        $item = $this->createMock(cs_item::class);
        $this->stubBase($item, creator: 'Ada Lovelace', fileCount: 2);

        $payload = (new NotificationPayloadFactory())->fromItem($item);

        self::assertSame('Ada Lovelace', $payload->creatorName);
        self::assertTrue($payload->hasAttachments);
        self::assertNull($payload->place);
        self::assertNull($payload->materialAuthor);
    }

    public function testDateItemCarriesTimeAndPlace(): void
    {
        $item = $this->createMock(cs_dates_item::class);
        $this->stubBase($item, creator: 'Grace Hopper', fileCount: 0);
        $item->method('getPlace')->willReturn('Room 7');
        $item->method('getDateTime_start')->willReturn('2026-06-20 09:00:00');
        $item->method('getDateTime_end')->willReturn('2026-06-20 10:00:00');
        $item->method('isWholeDay')->willReturn(false);

        $payload = (new NotificationPayloadFactory())->fromItem($item);

        self::assertSame('Room 7', $payload->place);
        self::assertSame('2026-06-20 09:00:00', $payload->dateStart);
        self::assertSame('2026-06-20 10:00:00', $payload->dateEnd);
        self::assertFalse($payload->wholeDay);
        self::assertFalse($payload->hasAttachments);
    }

    public function testMaterialItemCarriesAuthorAndPublishingDate(): void
    {
        $item = $this->createMock(cs_material_item::class);
        $this->stubBase($item, creator: 'Ada', fileCount: 1);
        $item->method('getAuthor')->willReturn('External Author');
        $item->method('getPublishingDate')->willReturn('2026-06-01');

        $payload = (new NotificationPayloadFactory())->fromItem($item);

        self::assertSame('External Author', $payload->materialAuthor);
        self::assertSame('2026-06-01', $payload->publishingDate);
        self::assertTrue($payload->hasAttachments);
    }

    public function testBlankAndZeroDatesBecomeNull(): void
    {
        $item = $this->createMock(cs_dates_item::class);
        $this->stubBase($item, creator: null, fileCount: 0);
        $item->method('getPlace')->willReturn('');
        $item->method('getDateTime_start')->willReturn('0000-00-00 00:00:00');
        $item->method('getDateTime_end')->willReturn('');
        $item->method('isWholeDay')->willReturn(true);

        $payload = (new NotificationPayloadFactory())->fromItem($item);

        self::assertNull($payload->place);
        self::assertNull($payload->dateStart);
        self::assertNull($payload->dateEnd);
        self::assertTrue($payload->wholeDay);
        self::assertNull($payload->creatorName);
    }

    private function stubBase(cs_item $item, ?string $creator, int $fileCount): void
    {
        if ($creator !== null) {
            $user = $this->createMock(cs_user_item::class);
            $user->method('getFullName')->willReturn($creator);
            $item->method('getCreatorItem')->willReturn($user);
        } else {
            $item->method('getCreatorItem')->willReturn(null);
        }

        $list = $this->createMock(cs_list::class);
        $list->method('getCount')->willReturn($fileCount);
        $item->method('getFileList')->willReturn($list);
    }
}
