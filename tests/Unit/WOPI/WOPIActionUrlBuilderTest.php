<?php


namespace Tests\Unit\WOPI;

use App\WOPI\ActionUrlBuilder;
use Codeception\Test\Unit;
use DG\BypassFinals;
use Tests\Support\UnitTester;

class WOPIActionUrlBuilderTest extends Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
        BypassFinals::enable();
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
        $this->assertEquals(1, substr_count(urldecode($actionUrl), 'wopisrc'));
    }

    public function testWopiSourceCount()
    {
        /**
         * Check that there is only a single instance of the wopisrc parameter
         */
        $onlyOfficeWithSource = 'https://host/hosting/wopi/cell/edit?<rs=DC_LLCC&><dchat=DISABLE_CHAT&><embed=EMBEDDED&><fs=FULLSCREEN&><hid=HOST_SESSION_ID&><rec=RECORDING&><sc=SESSION_CONTEXT&><thm=THEME_ID&><ui=UI_LLCC&><wopisrc=WOPI_SOURCE&>&';

        $actionUrlBuilder = new ActionUrlBuilder();
        $actionUrl = $actionUrlBuilder
            ->setWOPISource('https://host/wopi/files/abcef123')
            ->build($onlyOfficeWithSource);

        $this->assertStringContainsString('wopisrc=https://host/wopi/files/abcef123', urldecode($actionUrl));
        $this->assertEquals(1, substr_count(urldecode($actionUrl), 'wopisrc'));
    }
}
