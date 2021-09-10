<?php

namespace App\Tests\Acceptance;

use App\Tests\AcceptanceTester;
use App\Tests\Page\Acceptance\GroupFeed;
use App\Tests\Page\Login;
use App\Tests\Page\PortalCreate;
use App\Tests\Page\RoomCreate;


class RoomCest
{
    public function createRoom(AcceptanceTester $I, Login $loginPage, PortalCreate $portalCreatePage, RoomCreate $roomCreatePage)
    {
        $loginPage->login('root', 'root');
        $portalCreatePage->create('Mein Portal', 'Meine Beschreibung');

        $portalId = $I->grabFromCurrentUrl('~\/\?cid=(\d+)~');

        $roomCreatePage->createProjectRoomLegacy($portalId, 'Mein Projektraum', 'Beschreibung für meinen Raum');

        // TODO: This will currently fail due to RFC 2822 mail validation
//        $I->see('Mein Projektraum');
    }

    public function actionLinks(AcceptanceTester $I, Login $loginPage, PortalCreate $portalCreatePage, RoomCreate $roomCreatePage, GroupFeed $groupFeedPage)
    {
        $loginPage->login('root', 'root');
        $portalCreatePage->create('Mein Portal', 'Meine Beschreibung');

        $portalId = $I->grabFromCurrentUrl('~\/\?cid=(\d+)~');
        $roomCreatePage->createProjectRoomLegacy($portalId, 'Mein Projektraum', 'Beschreibung für meinen Raum');

//        $I->amOnPage('?cid=' . $portalId);

        $I->amOnPage('/room/103');
        $I->see('Die neuesten Einträge in diesem Raum');

        $I->amOnPage('/room/103/group');
        $actionData = $I->grabAttributeFrom('(//a[@class="commsy-select-action"])[last()]', 'data-cs-action');
        $dataArray = json_decode($actionData, true);
        $url = $dataArray['url'];

        $I->assertContains('/group/xhr/delete', $url);
    }
}
