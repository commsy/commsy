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

use App\Entity\Room;
use App\Entity\User;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class GotoControllerTest extends AbstractApplicationTestCase
{
    public function testGotoRedirectsToRoomHomeForProjectItem(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');

        $this->client->request('GET', "/goto/{$room->getItemId()}");

        $this->assertResponseRedirects("/room/{$room->getItemId()}");
    }

    public function testGotoRedirectsToDetailForRubricItem(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $creator */
        $creator = RoomWithMemberStory::get('roomUser');

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $creator,
        ]);

        $this->client->request('GET', "/goto/{$announcement->getItemId()}");

        $this->assertResponseRedirects(
            "/room/{$room->getItemId()}/announcement/{$announcement->getItemId()}"
        );
    }

    public function testGotoReturns404ForUnknownItem(): void
    {
        $this->client->request('GET', '/goto/'.PHP_INT_MAX);

        $this->assertResponseStatusCodeSame(404);
    }
}
