<?php

namespace Tests\Functional;

use Codeception\Util\HttpCode;
use Tests\Support\Step\Functional\Root;
use Tests\Support\Step\Functional\User;

class AccountCest
{
    public function accessAccount(Root $R, User $U)
    {
        $R->loginAndCreatePortalAsRoot();
        $U->registerAndLoginAsUser();
        $R->goToLogoutPath();

        $U->amOnRoute('app_account_personal', [
            'portalId' => 1,
        ]);

        $U->seeResponseCodeIs(HttpCode::OK);
    }
}
