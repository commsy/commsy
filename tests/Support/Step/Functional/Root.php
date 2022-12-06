<?php
namespace Tests\Support\Step\Functional;

use Tests\Support\FunctionalTester;
use Tests\Support\Page\Functional\Portal;

class Root extends FunctionalTester
{
    private Portal $portalPage;

    public function _inject(Portal $portalPage)
    {
        $this->portalPage = $portalPage;
    }

    public function loginAsRoot()
    {
        $I = $this;

        $I->amOnPage('/login/server');
        $I->see('Login Systemadministration');

        $I->fillField('#inputEmail', 'root');
        $I->fillField('#inputPassword', 'pcxEmQj6QzE5');
        $I->click('button[name=login_local]');
    }

    public function loginAndCreatePortalAsRoot(string $title = 'Testportal')
    {
        $I = $this;

        $I->loginAsRoot();
        $this->portalPage->create($title);
    }
}
