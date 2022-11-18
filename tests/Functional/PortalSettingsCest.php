<?php

namespace App\Tests\Functional;

use App\Tests\Step\Functional\Root;

class PortalSettingsCest
{
    public function roomTermsTemplates(Root $I)
    {
        $portal = $I->havePortal('Testportal');
        $I->amLoggedInAsRoot();

        $I->amOnRoute('app_portalsettings_roomtermstemplates', [
            'portalId' => $portal->getId(),
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Vorlagen Nutzungsbedingungen');
    }
}
