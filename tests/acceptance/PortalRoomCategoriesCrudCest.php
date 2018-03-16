<?php


class PortalRoomCategoriesCrudCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function viewPage(AcceptanceTester $I)
    {
        $I->amOnPage('/');

        $I->fillField('input[name="user_id"]', 'root');
        $I->fillField('input[name="password"]', 'root');
        $I->click('form[name="login"] input[type="submit"]');
        $I->see('Portal initialisieren');

        $I->click('Portal initialisieren');
        $I->seeInField('option', 'Einstellungen speichern');

        $I->fillField('//input[@name="title"]', 'Portal');
        $I->click('input[value="Einstellungen speichern"]');
        $I->see('Herzlich Willkommen');
    }
}
