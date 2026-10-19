<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Hash\HashManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\CalendarsFactory;
use Tests\Factory\DatesFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class ICalControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $roomUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
    }

    public function testGetContentServesICalWithValidHash(): void
    {
        $roomId = $this->room->getItemId();
        $this->seedDefaultCalendar();

        /** @var HashManager $hashManager */
        $hashManager = self::getContainer()->get(HashManager::class);
        $hash = $hashManager->getUserHashes($this->roomUser->getItemId());

        $this->client->request('GET', "/ical/{$roomId}?hid={$hash->getIcal()}");

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith(
            'text/calendar',
            $this->client->getResponse()->headers->get('Content-Type') ?? ''
        );
        $body = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('END:VCALENDAR', $body);
    }

    public function testGetContentRejectsInvalidHash(): void
    {
        $roomId = $this->room->getItemId();

        // nonexistent hash → controller throws createAccessDeniedException
        $this->client->request('GET', "/ical/{$roomId}?hid=notarealhash");

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    public function testGetContentRejectsWithoutHashOrGuestAccess(): void
    {
        $roomId = $this->room->getItemId();

        // room is NOT open for guests and no hash is provided → access denied
        $this->client->request('GET', "/ical/{$roomId}");

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }

    /**
     * Pre-create the room's default calendar so the controller's
     * `getDefaultCalendarId()` fallback (which lazily creates a calendar
     * using the room's creator) is not exercised — RoomFactory builds the
     * room via direct SQL without setting a creator.
     */
    /**
     * The feed of a room lists the dates of its default calendar: deleted
     * dates, dates of another calendar and, unless exported, dates older than
     * 13 months stay out; a private date is marked; participants are attendees.
     */
    public function testTheFeedListsTheDatesOfTheCalendar(): void
    {
        $this->seedDefaultCalendar();
        $soon = date('Y-m-d H:i:s', strtotime('+1 day'));
        $planning = $this->createDate('Planung', $soon);
        $this->createDate('Vertraulich', $soon, ['date_mode' => 1]);
        $this->createDate('Vorjahr', date('Y-m-d H:i:s', strtotime('-20 months')));
        $this->createDate('Geloescht', $soon, ['deletion_date' => $soon, 'deleter_id' => $this->roomUser->getItemId()]);
        $elsewhere = $this->createDate('Anderswo', $soon);
        $other = CalendarsFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser, 'title' => 'Andere'])->getId();
        $this->connection()->update('dates', ['calendar_id' => $other], ['item_id' => $elsewhere]);
        $this->linkParticipant($planning);

        $feed = $this->feed('');
        $this->assertStringContainsString('SUMMARY:Planung', $feed);
        $this->assertMatchesRegularExpression('/SUMMARY:Vertraulich \[[^\]]+\]/', $feed);
        $this->assertStringContainsString('ATTENDEE', $feed);
        $this->assertStringNotContainsString('Vorjahr', $feed);
        $this->assertStringNotContainsString('Geloescht', $feed);
        $this->assertStringNotContainsString('Anderswo', $feed);

        $this->assertStringContainsString('SUMMARY:Vorjahr', $this->feed('&export=1'));
    }

    private function feed(string $query): string
    {
        $hash = self::getContainer()->get(HashManager::class)->getUserHashes($this->roomUser->getItemId());
        $this->client->request('GET', "/ical/{$this->room->getItemId()}?hid={$hash->getIcal()}$query");
        $this->assertResponseIsSuccessful();

        return (string) $this->client->getResponse()->getContent();
    }

    /**
     * @param array<string, mixed> $columns
     */
    private function createDate(string $title, string $start, array $columns = []): int
    {
        $itemId = (int) DatesFactory::createOne(['title' => $title, 'room' => $this->room, 'creator' => $this->roomUser])->getItemId();
        $this->connection()->update('dates', ['datetime_start' => $start, 'datetime_end' => $start] + $columns, ['item_id' => $itemId]);

        return $itemId;
    }

    private function linkParticipant(int $dateId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->connection()->insert('items', ['context_id' => $this->room->getItemId(), 'modification_date' => $now, 'type' => 'link_item']);
        $this->connection()->insert('link_items', [
            'item_id' => (int) $this->connection()->lastInsertId(),
            'first_item_id' => $dateId, 'first_item_type' => 'date',
            'second_item_id' => $this->roomUser->getItemId(), 'second_item_type' => 'user',
            'context_id' => $this->room->getItemId(), 'modification_date' => $now,
        ]);
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    private function seedDefaultCalendar(): void
    {
        CalendarsFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'defaultCalendar' => true,
            'title' => 'Standard',
        ]);
    }
}
