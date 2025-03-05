<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Tests\Unit\WOPI;

use App\WOPI\ActionUrlBuilder;
use PHPUnit\Framework\TestCase;

class WOPIActionUrlBuilderTest extends TestCase
{
    public function testLegacyWopiSourceMissing(): void
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

    public function testLegacyWopiSourceNoDuplicate(): void
    {
        $wopiWithSource = 'https://host/we/wordeditorframe.aspx?<ui=UI_LLCC&><rs=DC_LLCC&><dchat=DISABLE_CHAT&><hid=HOST_SESSION_ID&><showpagestats=PERFSTATS&><IsLicensedUser=BUSINESS_USER&><actnavid=ACTIVITY_NAVIGATION_ID&><wopisrc=WOPI_SOURCE&>';

        $actionUrlBuilder = new ActionUrlBuilder();
        $actionUrl = $actionUrlBuilder
            ->setWOPISource('https://host/wopi/files/abcef123')
            ->build($wopiWithSource);

        $numSrc = substr_count(urldecode($actionUrl), 'wopisrc=https://host/wopi/files/abcef123');
        $this->assertEquals(1,  $numSrc);
    }
}
