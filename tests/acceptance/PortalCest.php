<?php


class PortalCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function createPortal(AcceptanceTester $I, \Page\Login $loginPage)
    {
        $loginPage->login('root', 'root');

        $I->amOnPage('/');
        $I->click('Portal initialisieren');
        $I->fillField('//input[@name="title"]', 'Portal');
        $I->click('input[value="Einstellungen speichern"]');
        $I->see('Raumübersicht');
    }
}
