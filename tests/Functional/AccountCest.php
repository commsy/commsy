<?php

namespace Tests\Functional;

use Tests\Support\Step\Functional\User;
use Codeception\Util\HttpCode;

class AccountCest
{
    public function accessAccount(User $I)
    {
        $portal = $I->havePortal('Testportal');
        $I->haveAccount($portal, 'user');

        $I->amOnRoute('app_account_personal', [
            'portalId' => $portal->getId(),
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
