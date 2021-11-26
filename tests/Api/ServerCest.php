<?php

namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class ServerCest
{
    public function _before(ApiTester $I)
    {
        $I->sendPostAsJson('/login_check', [
            'username' => 'api',
            'password' => 'apisecret',
        ]);
        $token = $I->grabDataFromResponseByJsonPath('$.token')[0];
        $I->amBearerAuthenticated($token);
    }

    // tests
    public function getPortalAnnouncement(ApiTester $I)
    {
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/server/announcement');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'enabled' => 'boolean',
            'title' => 'string',
            'severity' => 'string',
            'text' => 'string',
        ]);
    }
}
