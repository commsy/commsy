<?php
namespace App\Tests\Step\Functional;

class User extends \App\Tests\FunctionalTester
{
    public function login(string $portalTitle)
    {
        $I = $this;

        $I->amOnRoute('app_server_show');
        $I->click(['link' => $portalTitle]);

        $I->dontSee('Login Systemadministration');

        $I->fillField('#inputEmail', 'some@mail.test');
        $I->fillField('#inputPassword', 'ZSzq9z3aH8xDmGnLip');
        $I->click('button[name=login_local]');
    }

    public function register(string $portalTitle)
    {
        $I = $this;

        $I->amOnRoute('app_server_show');
        $I->click(['link' => $portalTitle]);
        $I->click(['link' => 'Sign up']);

        $I->fillField(['name' => 'sign_up_form[firstname]'], 'Firstname');
        $I->fillField(['name' => 'sign_up_form[lastname]'], 'Lastname');
        $I->fillField(['name' => 'sign_up_form[email]'], 'some@mail.test');
        $I->fillField(['name' => 'sign_up_form[username]'], 'username');
        $I->fillField(['name' => 'sign_up_form[plainPassword][first]'], 'ZSzq9z3aH8xDmGnLip');
        $I->fillField(['name' => 'sign_up_form[plainPassword][second]'], 'ZSzq9z3aH8xDmGnLip');
        $I->click('Submit');
    }

}