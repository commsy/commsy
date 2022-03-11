<?php

namespace App\Tests\Api;

use App\Entity\AuthSource;
use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class AuthSourceCest
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
//    public function getPortalAuthSources(ApiTester $I)
//    {
//        $portal = $I->havePortal('Some portal');
//
//        $I->sendGet('/portals/' . $portal->getId() . '/auth_sources');
//
//        $I->seeResponseCodeIs(HttpCode::OK);
//        $I->seeResponseIsJson();
//
//        $I->seeResponseJsonMatchesJsonPath('$[0].id');
//        $I->seeResponseJsonMatchesJsonPath('$[0].title');
//        $I->seeResponseJsonMatchesJsonPath('$[0].type');
//        $I->seeResponseJsonMatchesJsonPath('$[0].enabled');
//    }

//    public function getAuthSource(ApiTester $I)
//    {
//        $portal = $I->havePortal('Some portal');
//        /** @var AuthSource $authSource */
//        $authSource = $portal->getAuthSources()->first();
//
//        $I->sendGet('/auth_source/' . $authSource->getId());
//
//        $I->seeResponseCodeIs(HttpCode::OK);
//        $I->seeResponseIsJson();
//
//        $I->seeResponseJsonMatchesJsonPath('$.id');
//        $I->seeResponseJsonMatchesJsonPath('$.title');
//        $I->seeResponseJsonMatchesJsonPath('$.type');
//        $I->seeResponseJsonMatchesJsonPath('$.enabled');
//    }
//
//    public function getAuthSourceDirectLoginUrl(ApiTester $I)
//    {
//        $portal = $I->havePortal('Some portal');
//        /** @var AuthSource $authSource */
//        $authSource = $portal->getAuthSources()->first();
//
//        $I->sendGet('/auth_source/' . $authSource->getId() . '/login_url');
//
//        $I->seeResponseCodeIs(HttpCode::OK);
//        $I->seeResponseIsJson();
//
//        $I->seeResponseMatchesJsonType([
//            'url' => 'string:url|null',
//        ]);
//    }
}
