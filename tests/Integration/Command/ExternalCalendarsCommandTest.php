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

namespace Tests\Integration\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Factory\CalendarsFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The sync of external calendars fetches only a web address: an address of
 * a file on the server is not read.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ExternalCalendarsCommandTest extends KernelTestCase
{
    public function testTheAddressOfAFileOnTheServerIsNotRead(): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'feed');
        file_put_contents($file, "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//CommSy Test//EN\r\nBEGIN:VEVENT\r\nUID:local@example.org\r\nDTSTAMP:20260101T080000Z\r\nDTSTART:20300303T100000Z\r\nDTEND:20300303T113000Z\r\nSUMMARY:Lokal\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        try {
            $room = RoomWithMemberStory::get('room');
            $calendar = CalendarsFactory::createOne(['room' => $room, 'creator' => RoomWithMemberStory::get('roomUser'), 'title' => 'Extern']);
            $connection = self::getContainer()->get(Connection::class);
            $connection->update('calendars', ['external_url' => 'file://'.$file], ['id' => $calendar->getId()]);

            $tester = new CommandTester((new Application(self::bootKernel()))->find('commsy:cron:externalcalendars'));
            $tester->execute([]);

            self::assertSame(0, (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM dates WHERE title = ? AND context_id = ?',
                ['Lokal', $room->getItemId()],
            ));
            self::assertStringContainsString('no valid url', $tester->getDisplay());
        } finally {
            @unlink($file);
        }
    }
}
