<?php

namespace Tests\Functional;

use Tests\Support\Step\Functional\User;
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
        // create portal & room with settings that facilitate auto-creation of room user(s)
        $portal = $I->havePortal('Test portal', [
            'authMembershipEnabled' => true,
            'authMembershipIdentifier' => 'roomslugs',
        ]);

        $room = $I->haveProjectRoom('Test room', true, $portal);
        $room->setSlug('test-room');
        $room->save();

        $account = $I->haveAccount($portal, 'user2');

        $I->setServerParameters(['roomslugs' => 'test-room']);

        // given the above, login should cause room user(s) for the currently logged-in account to be created
        $I->amLoggedInAsUser($portal, 'user2', $account->getPlainPassword());

        // check if the room can be accessed successfully for the newly logged-in account
        $I->amOnRoute('app_room_home', [
            'roomId' => $room->getItemId(),
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
