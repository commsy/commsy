<?php

namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class AccountsCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
    }

    // tests
    public function incorrectPayload(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');
        $account = $I->haveAccount($portal->getAuthSources()->first(), 'username', 'mypassword');

        $I->sendPostAsJson('/accounts/checkLocalLogin');
        $I->seeResponseCodeIsClientError();
    }

    public function wrongCredentials(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');
        $account = $I->haveAccount($portal->getAuthSources()->first(), 'username', 'mypassword');

        $I->sendPostAsJson('/accounts/checkLocalLogin', [
            'contextId' => $portal->getId(),
            'username' => 'notauser',
            'password' => 'somepassword',
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function validCredentials(ApiTester $I)
    {
        $I->amReadOnlyAuthenticated();
        $portal = $I->havePortal('Some portal');
        $account = $I->haveAccount($portal->getAuthSources()->first(), 'username', 'mypassword');

        $I->sendPostAsJson('/accounts/checkLocalLogin', [
            'contextId' => $portal->getId(),
            'username' => 'username',
            'password' => 'mypassword',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            'username' => 'string',
            'firstname' => 'string',
            'lastname' => 'string',
            'email' => 'string',
            'locked' => 'boolean',
        ]);

        $I->seeResponseContainsJson([
            'username' => $account->getUsername(),
            'firstname' => $account->getFirstname(),
            'lastname' => $account->getLastname(),
            'email' => $account->getEmail(),
            'locked' => $account->isLocked(),
        ]);
    }
}
