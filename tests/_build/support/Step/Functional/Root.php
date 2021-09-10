<?php
namespace App\Tests\Step\Functional;

class Root extends \App\Tests\FunctionalTester
{
    public function loginAsRoot()
    {
        $I = $this;

        $I->amOnPage('/login/server');
        $I->see('Login Systemadministration');

        $I->fillField('#inputEmail', 'root');
        $I->fillField('#inputPassword', 'ZSzq9z3aH8xDmGnLip');
        $I->click('button[name=login_local]');
    }

    public function createPortal(string $title)
    {
        $I = $this;

        $I->amOnRoute('app_server_createportal');

        $I->fillField(['name' => 'portal[title]'], $title);
        $I->click(['name' => 'portal[submit]']);

        $I->seeCurrentRouteIs('app_server_show');
    }
}