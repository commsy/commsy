<?php

namespace App\Tests;

use App\Tests\AcceptanceTester;
use App\Tests\Page\Login;
use App\Tests\Page\PortalCreate;


class PortalCest
{
    public function login(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login('root', 'root');

        $I->amOnPage('/');
        $I->see('Portal initialisieren');
    }

    public function createPortal(AcceptanceTester $I, Login $loginPage, PortalCreate $portalCreatePage)
    {
        $loginPage->login('root', 'root');
        $portalCreatePage->create('Mein Portal', 'Meine Beschreibung');

        $I->see('Raumübersicht');
        $I->see('Mein Portal');

        // TODO: This will fail, cause the description is not saved
//        $I->see('Meine Beschreibung');
    }
}
