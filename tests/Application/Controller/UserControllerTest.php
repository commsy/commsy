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

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class UserControllerTest extends AbstractApplicationTestCase
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

    public function testListRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user");
        $this->assertResponseIsSuccessful();
    }

    public function testGridViewRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/gridView");
        $this->assertResponseIsSuccessful();
    }

    public function testFeedRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/feed/0/date");
        $this->assertResponseIsSuccessful();
    }

    public function testGridFeedRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/grid/0/date");
        $this->assertResponseIsSuccessful();
    }

    public function testDetailRenders(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/user/{$this->roomUser->getItemId()}"
        );
        $this->assertResponseIsSuccessful();
    }

    public function testInitialsRenders(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/user/{$this->roomUser->getItemId()}/initials"
        );
        $this->assertResponseIsSuccessful();
    }

    public function testSendFormRenders(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/user/{$this->roomUser->getItemId()}/send"
        );
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testGuestImageRenders(): void
    {
        $this->client->request('GET', '/room/user/guestimage');
        $this->assertResponseIsSuccessful();
    }
}
