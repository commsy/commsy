<?php
namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class MetricCest
{
    // tests
    public function getUnauthorized(ApiTester $I)
    {
        $I->sendGet('/metrics');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);

        $I->amHttpAuthenticated('commsy', 'wrong');
        $I->sendGet('/metrics');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function getAuthorized(ApiTester $I)
    {
        $I->amHttpAuthenticated('commsy', 'metricssecret');
        $I->sendGet('/metrics');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContains('php_info');
    }
}
