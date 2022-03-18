<?php

namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class ServerCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
    }

    // tests
    public function listServersFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $I->sendGet('/servers');

        $I->seeResponseJsonMatchesJsonPath('$[0].id');
    }

    public function listServersReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->sendGet('/servers');

        $I->seeResponseJsonMatchesJsonPath('$[0].id');
    }

    public function getServerFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $I->sendGet('/servers/99');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseJsonMatchesJsonPath('$.id');
    }

    public function getServerFullReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->sendGet('/servers/99');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseJsonMatchesJsonPath('$.id');
    }

    public function getServerNotFound(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->sendGet('/servers/123');

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function getServerAnnouncementFull(ApiTester $I)
    {
        $I->amFullAuthenticated();
        $I->sendGet('/servers/99/announcement');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'enabled' => 'boolean',
            'title' => 'string',
            'severity' => 'string',
            'text' => 'string',
        ]);
    }

    public function getServerAnnouncementReadOnly(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $I->sendGet('/servers/99/announcement');

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
