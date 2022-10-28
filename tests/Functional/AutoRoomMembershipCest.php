<?php

namespace App\Tests\Functional;

use App\Tests\Step\Functional\User;
use Codeception\Util\HttpCode;

class AutoRoomMembershipCest
{
    /**
     * Tests that a room user gets automatically created for an account on login if the login request
     * contains a key (as defined in the portal) with the room's unique textual identifier (slug).
     *
     * @param User $I
     */
    public function autoCreateRoomMember(User $I)
    {
        $portal = $I->havePortal('Test portal');

        // TODO set required Portal configs
        // setAuthMembershipEnabled: true
        // setAuthMembershipIdentifier: 'roomslugs'

        $room = $I->haveRoom('Test room', $portal, [
            'slug' => 'test-room',
        ]);

        $I->haveAccount($portal, 'user');

        // TODO set server params for next request via
        // $I->setServerParameters(['roomslugs' => 'test-room'])

        $I->amLoggedInAsUser($portal, 'Test user', 'testpwd');

        $I->amOnRoute('app_room_home', [
            'roomId' => $room->getItemId(),
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);

        // TODO check if the route for the newly created room user can be accessed successfully
    }
}
