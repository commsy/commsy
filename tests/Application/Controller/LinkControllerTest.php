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
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Note: the LinkController defines several methods that share the same URL
 * pattern (`/room/{roomId}/material/link/{itemId}`). Symfony's router only
 * dispatches to the first matching route, so only one of those methods is
 * reachable via HTTP. We cover what the router actually exposes.
 */
#[WithStory(RoomWithMemberStory::class)]
class LinkControllerTest extends AbstractApplicationTestCase
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

    public function testShowRendersForMaterialItem(): void
    {
        $material = MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);

        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/link/{$material->getItemId()}/material"
        );

        $this->assertResponseIsSuccessful();
    }

    public function testShowDetailRendersForMaterialItem(): void
    {
        $material = MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);

        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/material/link/{$material->getItemId()}"
        );

        $this->assertResponseIsSuccessful();
    }
}
