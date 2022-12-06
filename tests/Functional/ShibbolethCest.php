<?php

namespace Tests\Functional;

use Tests\Support\Page\Functional\PortalAuthShibboleth;
use Tests\Support\Step\Functional\Root;
use Tests\Support\Step\Functional\User;

class ShibbolethCest
{
    public function initiatorUrlAccessDenied(Root $R, User $U)
    {
        $R->loginAndCreatePortalAsRoot();
        $R->goToLogoutPath();

        // We assume that the default portal does not have a shibboleth source configured
        $U->amOnRoute('app_shibboleth_authshibbolethinit', [
            'portalId' => 1,
        ]);

        // The AccessDeniedException will result in a redirect to login page
        $U->seeCurrentRouteIs('app_login', [
            'context' => 1,
        ]);
    }

    public function initiatorUrlDefault(Root $R, User $I, PortalAuthShibboleth $portalAuthShibbolethPage)
    {
        $R->loginAndCreatePortalAsRoot();
        $portalAuthShibbolethPage->configure(1, true);
        $R->goToLogoutPath();

        $I->stopFollowingRedirects();
        $I->amOnRoute('app_shibboleth_authshibbolethinit', [
            'portalId' => 1,
        ]);

        $I->seeResponseCodeIsRedirection();
    }
}
