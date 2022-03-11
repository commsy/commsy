<?php

namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class PortalCest
{
    public function _before(ApiTester $I)
    {
        $I->sendPostAsJson('/login_check', [
            'username' => 'api',
            'password' => 'apisecret',
        ]);
        $token = $I->grabDataFromResponseByJsonPath('$.token')[0];
        $I->amBearerAuthenticated($token);

        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
    }

    // tests
    public function listPortals(ApiTester $I)
    {
        $I->havePortal('Some portal');

        $I->sendGet('/portals');

        $I->seeResponseJsonMatchesJsonPath('$[0].id');
        $I->seeResponseJsonMatchesJsonPath('$[0].creationDate');
        $I->seeResponseJsonMatchesJsonPath('$[0].modificationDate');
        $I->seeResponseJsonMatchesJsonPath('$[0].title');
    }

    public function getPortal(ApiTester $I)
    {
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId());

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->seeResponseJsonMatchesJsonPath('$.creationDate');
        $I->seeResponseJsonMatchesJsonPath('$.modificationDate');
        $I->seeResponseJsonMatchesJsonPath('$.title');
    }

    public function getPortalAnnouncement(ApiTester $I)
    {
        $portal = $I->havePortal('Some portal');

        $I->sendGet('/portals/' . $portal->getId() . '/announcement');

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
