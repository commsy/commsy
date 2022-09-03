<?php
namespace Tests\Support\Step\Functional;

use Tests\Support\FunctionalTester;

class Root extends FunctionalTester
{
    public function amLoggedInAsRoot()
    {
        $I = $this;

        $I->amOnPage('/login/server');
        $I->see('Login Systemadministration');

        $I->fillField('#inputEmail', 'root');
        $I->fillField('#inputPassword', 'pcxEmQj6QzE5');
        $I->click('button[name=login_local]');

//        /** @var Account $rootAccount */
//        $rootAccount = $I->grabEntityFromRepository(Account::class, [
//            'username' => 'root',
//        ]);
//        $I->amLoggedInAs($rootAccount);
    }
}
