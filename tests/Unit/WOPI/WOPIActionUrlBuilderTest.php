<?php


namespace Tests\Unit\WOPI;

use App\WOPI\ActionUrlBuilder;
use Codeception\Test\Unit;
use Tests\Support\UnitTester;

class WOPIActionUrlBuilderTest extends Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testLegacyWopiSourceMissing()
    {
        /**
         * Office 365 versions prior to 2018.12.15 required hosts to add the WopiSrc to the action URL because
         * there was no placeholder.
         */
        $legacy365WithoutSource = 'https://host/we/wordeditorframe.aspx?<ui=UI_LLCC&><rs=DC_LLCC&><dchat=DISABLE_CHAT&><hid=HOST_SESSION_ID&><showpagestats=PERFSTATS&><IsLicensedUser=BUSINESS_USER&><actnavid=ACTIVITY_NAVIGATION_ID&>';

        $actionUrlBuilder = new ActionUrlBuilder();
        $actionUrl = $actionUrlBuilder
            ->setWOPISource('https://host/wopi/files/abcef123')
            ->build($legacy365WithoutSource);

        $this->assertStringContainsString('wopisrc=https://host/wopi/files/abcef123', urldecode($actionUrl));
    }
}
