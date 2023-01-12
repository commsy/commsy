<?php

namespace Tests\Functional;

use Tests\Support\Page\Functional\PortalWorkspaceMembership;
use Tests\Support\Page\Functional\Room;
use Tests\Support\Step\Functional\Root;
use Tests\Support\Step\Functional\User;
use Codeception\Util\HttpCode;

class AutoRoomMembershipCest
{
    /**
     * Tests that a room user gets automatically created for an account on login if the login request
     * contains a key (as defined in the portal) with the room's unique textual identifier (slug).
     *
     * @param Root $R
     * @param User $U
     * @param PortalWorkspaceMembership $portalWorkspaceMembershipPage
     * @param Room $roomPage
     */
    public function autoCreateRoomMember(
        Root $R,
        User $U,
        PortalWorkspaceMembership $portalWorkspaceMembershipPage,
        Room $roomPage
    ) {
        $R->loginAndCreatePortalAsRoot();
        $portalWorkspaceMembershipPage->configure(1, true, 'roomslugs');
        $R->goToLogoutPath();

        $U->registerAndLoginAsUser(1);
        $roomPage->create(1, 'Testraum');
        $roomId = $U->grabFromCurrentUrl('~^/portal/\d+/room/(\d+)~');

        $U->amOnRoute('app_settings_general', [
            'roomId' => $roomId,
        ]);
        $U->fillField('#general_settings_room_slug', 'test-room');
        $U->click('#general_settings_save');

        $U->goToLogoutPath();

        $U->registerAsUser(1, 'username2');

        $U->setServerParameters(['roomslugs' => 'test-room']);

        // given the above, login should cause room user(s) for the currently logged-in account to be created
        $U->loginAsUser(1, 'username2');

        // check if the room can be accessed successfully for the newly logged-in account
        $U->amOnRoute('app_room_home', [
            'roomId' => $roomId,
        ]);

        $U->seeResponseCodeIs(HttpCode::OK);
    }
}
