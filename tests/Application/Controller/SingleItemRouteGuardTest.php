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
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Knp\Snappy\Pdf;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\DatesFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A route naming an entry checks the entry itself, not only the room in the
 * address: an entry of a room one has no access to is neither shown nor
 * changed through it.
 */
#[WithStory(RoomWithMemberStory::class)]
final class SingleItemRouteGuardTest extends AbstractApplicationTestCase
{
    private Connection $connection;
    private Room $room;
    /** @var array<string, int> */
    private array $foreign = [];
    /** @var array<string, int> */
    private array $own = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = self::getContainer()->get(Connection::class);
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection->update('user', ['status' => 3], ['item_id' => $roomUser->getItemId()]);

        $portal = $account->getPortal();
        $foreignRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $owner = RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $portal, 'authSource' => $account->getAuthSource()]),
            'room' => $foreignRoom,
            'status' => 3,
        ]);

        $this->foreign = [
            'material' => MaterialFactory::createOne(['room' => $foreignRoom, 'creator' => $owner, 'title' => 'Fremdes Material'])->getItemId(),
            'date' => DatesFactory::createOne(['room' => $foreignRoom, 'creator' => $owner, 'title' => 'Fremder Termin'])->getItemId(),
            'todo' => TodoFactory::createOne(['room' => $foreignRoom, 'creator' => $owner, 'title' => 'Fremde Aufgabe'])->getItemId(),
        ];
        $this->own = [
            'material' => MaterialFactory::createOne(['room' => $this->room, 'creator' => $roomUser, 'title' => 'Eigenes Material'])->getItemId(),
            'todo' => TodoFactory::createOne(['room' => $this->room, 'creator' => $roomUser, 'title' => 'Eigene Aufgabe'])->getItemId(),
        ];

        $this->loginAsUser((int) $portal?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function readingRoutes(): iterable
    {
        yield 'print' => ['material', '/material/%d/print'];
        yield 'annotations' => ['material', '/annotation/feed/%d/0'];
        yield 'send form' => ['material', '/%d/send'];
        yield 'file list' => ['material', '/item/%d/filelist'];
        yield 'links' => ['material', '/link/%d/material'];
    }

    #[DataProvider('readingRoutes')]
    public function testAForeignEntryIsNotShown(string $entry, string $route): void
    {
        $this->client->request('GET', $this->path($route, $this->foreign[$entry]));

        self::assertFalse($this->client->getResponse()->isSuccessful());
    }

    public function testAnOwnEntryIsStillShown(): void
    {
        // the print view renders a PDF; the renderer binary is not part of the test environment
        $pdf = $this->createStub(Pdf::class);
        $pdf->method('getOutputFromHtml')->willReturn('%PDF-1.4 stub');
        static::getContainer()->set('knp_snappy.pdf', $pdf);

        $this->client->request('GET', $this->path('/material/%d/print', $this->own['material']));

        self::assertResponseIsSuccessful();
    }

    public function testTheStatusOfAForeignTaskStaysAsItIs(): void
    {
        $before = $this->todoStatus($this->foreign['todo']);

        $this->client->xmlHttpRequest('POST', $this->path('/todo/xhr/changestatus/%d', $this->foreign['todo']), ['payload' => ['status' => 'done']]);

        self::assertSame($before, $this->todoStatus($this->foreign['todo']));
    }

    public function testTheStatusOfAnOwnTaskStillChanges(): void
    {
        $this->client->xmlHttpRequest('POST', $this->path('/todo/xhr/changestatus/%d', $this->own['todo']), ['payload' => ['status' => 'done']]);

        self::assertSame(3, $this->todoStatus($this->own['todo']));
    }

    public function testAForeignDateIsNotMoved(): void
    {
        $before = $this->connection->fetchOne('SELECT datetime_start FROM dates WHERE item_id = ?', [$this->foreign['date']]);

        $this->client->xmlHttpRequest('POST', $this->path('/date/%d/calendaredit', $this->foreign['date']), [], [], ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['start' => '2026-12-24T18:00:00.000+01:00', 'end' => '2026-12-24T19:00:00.000+01:00', 'allDay' => false]));

        self::assertSame($before, $this->connection->fetchOne('SELECT datetime_start FROM dates WHERE item_id = ?', [$this->foreign['date']]));
    }

    public function testAForeignEntryIsNotRated(): void
    {
        $this->client->xmlHttpRequest('POST', $this->path('/material/%d/rating/5', $this->foreign['material']));

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM assessments WHERE item_link_id = ?', [$this->foreign['material']]));
    }

    public function testNoFileIsAttachedToAForeignEntry(): void
    {
        $this->client->request('POST', $this->path('/attach/%d', $this->foreign['material']));

        self::assertFalse($this->client->getResponse()->isSuccessful());
    }

    private function path(string $route, int $itemId): string
    {
        return "/room/{$this->room->getItemId()}".sprintf($route, $itemId);
    }

    private function todoStatus(int $itemId): int
    {
        return (int) $this->connection->fetchOne('SELECT status FROM todos WHERE item_id = ?', [$itemId]);
    }
}
