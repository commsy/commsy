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
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\CalendarsFactory;
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
