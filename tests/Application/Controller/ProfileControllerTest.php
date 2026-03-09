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

namespace Tests\Application\Controller;

use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

class ProfileControllerTest extends AbstractApplicationTestCase
{
    #[WithStory(RoomWithMemberStory::class)]
    public function testGeneralSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/general");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_general_save', []);
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAddressSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/address");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_address_save', [
            'room_profile_address[street]' => 'Teststreet 1',
            'room_profile_address[city]' => 'Testcity',
            'room_profile_address[zipCode]' => '12345',
        ]);

        $this->assertResponseRedirects("/room/{$roomId}/user/{$itemId}/address");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testContactSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/contact");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_contact_save', [
            'room_profile_contact[phone]' => '123456789',
        ]);

        $this->assertResponseRedirects("/room/{$roomId}/user/{$itemId}/contact");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testNotificationsSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/notifications");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_save', [
            'room_profile[mail_account]' => true,
            'room_profile[mail_room]' => true,
            'room_profile[mail_item_deleted]' => false,
        ]);
        $this->assertResponseIsSuccessful();
    }
}
