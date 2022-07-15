<?php

namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class AuthorizationCest
{
    public function _before(ApiTester $I)
    {
    }

    // tests
    public function testAccessDenied(ApiTester $I)
    {
        $I->sendGet('/portals');

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseMatchesJsonType([
            'code' => 'integer',
            'message' => 'string',
        ]);
        $I->seeResponseContainsJson([
            'message' => 'JWT Token not found',
        ]);
    }

    public function testInvalidCredentials(ApiTester $I)
    {
        $I->sendPostAsJson('/login_check', [
            'username' => 'unknown',
            'password' => 'secret',
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseMatchesJsonType([
            'code' => 'integer',
            'message' => 'string',
        ]);
    }

    public function testValidCredentials(ApiTester $I)
    {
        $I->sendPostAsJson('/login_check', [
            'username' => 'api_write',
            'password' => 'apiwrite',
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType([
            'token' => 'string',
        ]);
    }
}
