<?php


class RoomCest
{
    public function createRoom(AcceptanceTester $I, \Page\Login $loginPage, \Page\PortalCreate $portalCreatePage, \Page\RoomCreate $roomCreatePage)
    {
        $loginPage->login('root', 'root');
        $portalCreatePage->create('Mein Portal', 'Meine Beschreibung');

        $portalId = $I->grabFromCurrentUrl('~\/\?cid=(\d+)~');

        $roomCreatePage->createProjectRoomLegacy($portalId, 'Mein Projektraum', 'Beschreibung für meinen Raum');

        // TODO: This will currently fail due to RFC 2822 mail validation
//        $I->see('Mein Projektraum');
    }

    public function actionLinks(AcceptanceTester $I, \Page\Login $loginPage, \Page\PortalCreate $portalCreatePage, \Page\RoomCreate $roomCreatePage, \Page\Acceptance\GroupFeed $groupFeedPage)
    {
        $loginPage->login('root', 'root');
        $portalCreatePage->create('Mein Portal', 'Meine Beschreibung');

        $portalId = $I->grabFromCurrentUrl('~\/\?cid=(\d+)~');
        $roomCreatePage->createProjectRoomLegacy($portalId, 'Mein Projektraum', 'Beschreibung für meinen Raum');

//        $I->amOnPage('?cid=' . $portalId);

        $I->amOnPage('/app_testsuite.php/room/103');
        $I->see('Die neuesten Einträge in diesem Raum');

        $I->amOnPage('/app_testsuite.php/room/103/group');
        $actionData = $I->grabAttributeFrom('(//a[@class="commsy-select-action"])[last()]', 'data-cs-action');
        $dataArray = json_decode($actionData, true);
        $url = $dataArray['url'];

        $I->assertContains('/group/xhr/delete', $url);
    }
}
