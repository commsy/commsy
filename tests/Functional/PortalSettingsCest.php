<?php

namespace Tests\Functional;

use Tests\Support\Step\Functional\Root;

class PortalSettingsCest
{
    public function roomTermsTemplates(Root $R)
    {
        $R->loginAndCreatePortalAsRoot();

        $R->amOnRoute('app_portalsettings_roomtermstemplates', [
            'portalId' => 1,
        ]);

        $R->seeResponseCodeIsSuccessful();
        $R->see('Vorlagen Nutzungsbedingungen');
    }
}
