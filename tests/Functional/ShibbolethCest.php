<?php

namespace App\Tests\Functional;

use App\Entity\AuthSourceShibboleth;
use App\Tests\Step\Functional\User;

class ShibbolethCest
{
    public function initiatorUrlAccessDenied(User $I)
    {
        $portal = $I->havePortal('Testportal');

        // We assume that the default portal does not have a shibboleth source configured
        $I->amOnRoute('app_shibboleth_authshibbolethinit', [
            'portalId' => $portal->getId(),
        ]);

        // The AccessDeniedException will result in a redirect to login page
        $I->seeCurrentRouteIs('app_login', [
            'context' => $portal->getId(),
        ]);
    }

    public function initiatorUrlDefault(User $I)
    {
        $portal = $I->havePortal('Testportal');

        $shibSource = new AuthSourceShibboleth();
        $shibSource->setLoginUrl('https://example.com');
        $I->haveAuthSource($portal, $shibSource, 'Shib');

        $I->stopFollowingRedirects();
        $I->amOnRoute('app_shibboleth_authshibbolethinit', [
            'portalId' => $portal->getId(),
        ]);

        $I->seeResponseCodeIsRedirection();
    }
}
