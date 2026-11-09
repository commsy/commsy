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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * An import reads only calendar files uploaded for the room: the form hands
 * the name of each upload back, and a name that leads elsewhere is not read.
 */
#[WithStory(RoomWithMemberStory::class)]
final class DateImportPathTest extends AbstractApplicationTestCase
{
    private const string FEED = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//CommSy Test//EN\r\nBEGIN:VEVENT\r\nUID:%s@example.org\r\nDTSTAMP:20260101T080000Z\r\nDTSTART:20300303T100000Z\r\nDTEND:20300303T113000Z\r\nSUMMARY:%s\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

    private Room $room;
    private string $temp;

    /** @var list<string> */
    private array $written = [];

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->temp = self::getContainer()->getParameter('kernel.project_dir').'/var/temp';
        @mkdir($this->temp, 0777, true);

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function testANameLeadingOutOfTheUploadsIsNotRead(): void
    {
        $this->write(dirname($this->temp).'/outside_'.uniqid().'.ics', 'Fremd');

        $this->import('../'.basename(end($this->written)));

        self::assertSame(0, $this->datesTitled('Fremd'));
    }

    public function testAnUploadOfAnotherRoomIsNotRead(): void
    {
        $name = ($this->room->getItemId() + 1).'_'.uniqid().'.ics';
        $this->write($this->temp.'/'.$name, 'Anderer Raum');

        $this->import($name);

        self::assertSame(0, $this->datesTitled('Anderer Raum'));
    }

    public function testAnUploadForTheRoomIsImported(): void
    {
        $name = $this->room->getItemId().'_'.uniqid().'.ics';
        $this->write($this->temp.'/'.$name, 'Eigener Raum');

        $this->import($name);

        self::assertSame(1, $this->datesTitled('Eigener Raum'));
    }

    private function write(string $path, string $title): void
    {
        file_put_contents($path, sprintf(self::FEED, uniqid(), $title));
        $this->written[] = $path;
    }

    private function import(string $fileId): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->room->getItemId()}/date/import");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('upload[save]')->form();
        $values = $form->getPhpValues();
        $values['upload']['calendar'] = 'new';
        $values['upload']['calendartitle'] = 'Import';
        $values['upload']['files'] = [[
            'checked' => '1',
            'fileId' => $fileId,
            'filename' => 'feed.ics',
            'filePath' => '',
        ]];
        $values['upload']['save'] = '';

        $this->client->request('POST', (string) $form->getUri(), $values);
        $this->assertResponseRedirects();
    }

    private function datesTitled(string $title): int
    {
        return (int) self::getContainer()->get(Connection::class)->fetchOne(
            'SELECT COUNT(*) FROM dates WHERE title = ? AND context_id = ?',
            [$title, $this->room->getItemId()],
        );
    }
}
