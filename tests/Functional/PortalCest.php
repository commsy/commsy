<?php

namespace App\Tests\Functional;

use App\Tests\Step\Functional\Root;

class PortalCest
{
    public function createPortal(Root $I)
    {
        $I->loginAsRoot();

        $I->amOnRoute('app_server_show');
        $I->seeLink('Portal erstellen');
        $I->click(['link' => 'Portal erstellen']);

        $I->createPortal('Testportal');

        $I->seeCurrentRouteIs('app_server_show');
        $I->see('Testportal');
    }
}
