<?php

namespace App\Tests\Functional;

use App\Tests\FunctionalTester;

class LoginCest
{
    public function loginAsRoot(FunctionalTester $I)
    {
        $I->amOnPage('/login/server');
        $I->see('Login Systemadministration');

        $I->fillField('#inputEmail', 'root');
        $I->fillField('#inputPassword', 'pcxEmQj6QzE5');
        $I->click('button[name=login_local]');

        $I->seeCurrentRouteIs('app_server_show');
    }

    public function loginAsUser(FunctionalTester $I)
    {
        $portal = $I->havePortal('Testportal');
        $account = $I->haveAccount($portal, 'user');

        $I->amOnRoute('app_login', [
            'context' => $portal->getId(),
        ]);

        $I->fillField('#inputEmail', $account->getUsername());
        $I->fillField('#inputPassword', $account->getPlainPassword());
        $I->click('button[name=login_local]');

        $I->seeCurrentRouteIs('app_dashboard_overview');

        // Make sure ...

        // TODO: Make sure the user is redirected if he gets to the login form again

        /**
         * TODO: This is very basic right now and we should check that the access to a room on a portal the user is
         * currently not logged in to is forbidden:
         * - If the user is already logged in, check he does not see another login form
         * - If the user is already logged in, check he gets a 404 forbidden when trying to acccess another room
         * - ...
         */
    }

    public function register(FunctionalTester $I)
    {
        $portal = $I->havePortal('Testportal');

        $I->amOnRoute('app_account_signup', [
            'id' => $portal->getId(),
        ]);

        $I->fillField(['name' => 'sign_up_form[firstname]'], 'Firstname');
        $I->fillField(['name' => 'sign_up_form[lastname]'], 'Lastname');
        $I->fillField(['name' => 'sign_up_form[email][first]'], 'some@mail.test');
        $I->fillField(['name' => 'sign_up_form[email][second]'], 'some@mail.test');
        $I->fillField(['name' => 'sign_up_form[username]'], 'username');
        $I->fillField(['name' => 'sign_up_form[plainPassword][first]'], 'zfCbzLm9h4$h');
        $I->fillField(['name' => 'sign_up_form[plainPassword][second]'], 'zfCbzLm9h4$h');
        $I->click(['name' => 'sign_up_form[submit]']);

        $I->seeCurrentRouteIs('app_login');
    }
}
