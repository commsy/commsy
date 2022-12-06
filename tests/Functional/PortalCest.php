<?php

namespace Tests\Functional;

use Tests\Support\Page\Functional\Portal;
use Tests\Support\Step\Functional\Root;

class PortalCest
{
    public function createPortal(Root $R, Portal $portalPage)
    {
        $R->loginAsRoot();

        $R->amOnRoute('app_server_show');
        $R->seeLink('Portal erstellen');
        $R->click(['link' => 'Portal erstellen']);

        $portalPage->create('Testportal');

        $R->seeCurrentRouteIs('app_server_show');
        $R->see('Testportal');
    }
}
