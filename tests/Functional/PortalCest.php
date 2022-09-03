<?php

namespace Tests\Functional;

use Tests\Support\Step\Functional\Root;

class PortalCest
{
    public function createPortal(Root $I)
    {
        $I->amLoggedInAsRoot();

        $I->amOnRoute('app_server_show');
        $I->seeLink('Portal erstellen');
        $I->click(['link' => 'Portal erstellen']);

        $I->amOnRoute('app_server_createportal');
        $I->fillField(['name' => 'portal_general[title]'], 'Testportal');
        $I->click(['name' => 'portal_general[save]']);

        $I->seeCurrentRouteIs('app_server_show');
        $I->see('Testportal');
    }
}
