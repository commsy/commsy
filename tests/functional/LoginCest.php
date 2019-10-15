<?php namespace App\Tests;
use App\Tests\Step\Functional\Root;
use App\Tests\Step\Functional\User;

class LoginCest
{
    public function loginAsRoot(Root $I)
    {
        $I->loginAsRoot();
        $I->seeCurrentRouteIs('app_server_show');
    }

    public function register(Root $I, User $U)
    {
        $I->loginAsRoot();
        $I->createPortal('Testportal');
        $I->amOnRoute('app_logout');

        $U->register('Testportal');
    }

    public function loginAsUser(Root $I, User $U)
    {
        // Create two portals as root
        $I->loginAsRoot();
        $I->createPortal('Portal A');
        $I->createPortal('Portal B');
        $I->amOnRoute('app_logout');

        // Register on both portals with the same username
        $U->register('Portal A');
        $U->amOnRoute('app_logout');
        $U->register('Portal B');
        $U->amOnRoute('app_logout');

        // This is the first login and the user will be redirected to the dashboard on success
        $U->login('Portal A');
        $U->seeCurrentRouteIs('app_dashboard_overview');

        // Make sure the user is redirected if he gets to the login form again
        $U->amOnRoute('app_server_show');
        $U->click(['link' => 'Portal A']);
        $U->seeCurrentRouteIs('app_dashboard_overview');

        // Make sure ...

        /**
         * TODO: This is very basic right now and we should check that the access to a room on a portal the user is
         * currently not logged in to is forbidden:
         * - If the user is already logged in, check he does not see another login form
         * - If the user is already logged in, check he gets a 404 forbidden when trying to acccess another room
         * - ...
         */
    }
}
