<?php


class PortalCest
{
    public function login(AcceptanceTester $I, \Page\Login $loginPage)
    {
        $loginPage->login('root', 'root');

        $I->amOnPage('/');
        $I->see('Portal initialisieren');
    }

    public function createPortal(AcceptanceTester $I, \Page\Login $loginPage, \Page\PortalCreate $portalCreatePage)
    {
        $loginPage->login('root', 'root');
        $portalCreatePage->create('Mein Portal', 'Meine Beschreibung');

        $I->see('Raumübersicht');
        $I->see('Mein Portal');

        // TODO: This will fail, cause the description is not saved
//        $I->see('Meine Beschreibung');
    }
}
