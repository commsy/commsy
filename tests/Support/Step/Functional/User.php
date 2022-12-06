<?php
namespace Tests\Support\Step\Functional;

use Tests\Support\FunctionalTester;
use Tests\Support\Page\Functional\Registration;

class User extends FunctionalTester
{
    private Registration $registrationPage;

    public function _inject(Registration $registrationPage)
    {
        $this->registrationPage = $registrationPage;
    }

    public function loginAsUser(int $portalId = 1, string $username = 'username', string $password = 'Q#aLKBD5h#!n')
    {
        $I = $this;

        $I->amOnRoute('app_logout');

        $I->amOnRoute('app_login', [
            'context' => $portalId,
        ]);

        $I->fillField('#inputEmail', $username);
        $I->fillField('#inputPassword', $password);
        $I->click('button[name=login_local]');
    }

    public function registerAsUser(int $portalId = 1, string $username = 'username', string $password = 'Q#aLKBD5h#!n')
    {
        $this->registrationPage->register(
            $portalId,
            'firstname',
            'lastname',
            $username,
            'user@name.test',
            $password
        );
    }

    public function registerAndLoginAsUser(int $portalId = 1, string $username = 'username', string $password = 'Q#aLKBD5h#!n')
    {
        $I = $this;

        $I->registerAsUser($portalId, $username, $password);
        $I->loginAsUser($portalId, $username, $password);
    }
}
