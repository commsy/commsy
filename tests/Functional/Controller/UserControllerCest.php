<?php

namespace Tests\Functional\Controller;

use Tests\Support\FunctionalTester;
use Tests\Support\Page\Functional\Room;
use Tests\Support\Step\Functional\Root;
use Tests\Support\Step\Functional\User;

class UserControllerCest
{
    private int $roomId;

    public function _before(Root $R, User $U, Room $roomPage)
    {
        $R->loginAndCreatePortalAsRoot();
        $R->goToLogoutPath();

        $U->registerAndLoginAsUser(1);
        $roomPage->create(1, 'Testraum');
        $this->roomId = $U->grabFromCurrentUrl('~^/portal/\d+/room/(\d+)~');
    }

    public function detail(FunctionalTester $I)
    {
        $I->amOnRoute('app_user_list', [
            'roomId' => $this->roomId,
        ]);
        $I->click('ul#user-feed article:first-child .uk-comment-title a');
        $userId = $I->grabFromCurrentUrl('~^/room/\d+/user/(\d+)~');

        $I->amOnRoute('app_user_detail', [
            'roomId' => $this->roomId,
            'itemId' => $userId,
        ]);
        $I->seeResponseCodeIsSuccessful();

        // Forbidden
        $I->goToLogoutPath();
        $I->stopFollowingRedirects();
        $I->amOnRoute('app_user_detail', [
            'roomId' => $this->roomId,
            'itemId' => $userId,
        ]);
        $I->seeResponseCodeIsRedirection();
    }

    public function feed(FunctionalTester $I)
    {
        $I->amOnRoute('app_user_feed', [
            'roomId' => $this->roomId,
        ]);
        $I->seeResponseCodeIsSuccessful();
    }

    public function list(FunctionalTester $I)
    {
        $I->amOnRoute('app_user_list', [
            'roomId' => $this->roomId,
        ]);
        $I->seeResponseCodeIsSuccessful();
    }
}
