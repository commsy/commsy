<?php

namespace App\Tests\Functional;

use App\Entity\AuthSourceLocal;
use App\Entity\Translation;
use App\Tests\FunctionalTester;
use App\Tests\Page\Functional\Registration;

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

    public function register(FunctionalTester $I, Registration $registrationPage)
    {
        $portal = $I->havePortal('Testportal');

        $registrationPage->register($portal, 'Firstname', 'Lastname', 'username',
            'some@mail.test', 'zfCbzLm9h4$h');

        $I->seeCurrentRouteIs('app_login');
    }

    public function registerWithBadPassword(FunctionalTester $I, Registration $registrationPage)
    {
        $portal = $I->havePortal('Testportal');

        $registrationPage->register($portal, 'Firstname', 'Lastname', 'username',
            'some@mail.test', 'badpassword');

        $I->see('Das eingegebene Passwort muss mindestens einen Großbuchstaben enthalten');
        $I->see('Das eingegebene Passwort muss mindestens ein Sonderzeichen enthalten');
        $I->see('Das eingegebene Passwort muss mindestens eine Zahl enthalten');
        $I->see('Das Passwort muss mindestens 8 Zeichen lang sein und mindestens einen Klein- und Großbuchstaben, sowie ein Sonderzeichen und eine Zahl enthalten');
    }

    public function registerWithUnallowedEmail(FunctionalTester $I, Registration $registrationPage)
    {
        $authSource = new AuthSourceLocal();
        $authSource->setTitle('Lokal');
        $authSource->setEnabled(true);
        $authSource->setDefault(true);
        $authSource->setMailRegex('~.*@domain.tld~');
        $I->haveInRepository($authSource);

        $portal = $I->havePortal('Testportal', [], $authSource);

        $I->haveInRepository(Translation::class, [
            'contextId' => $portal->getId(),
            'translationKey' => 'EMAIL_REGEX_ERROR',
            'translationDe' => 'error_de',
            'translationEn' => 'error_en',
        ]);

        $registrationPage->register($portal, 'Firstname', 'Lastname', 'username',
            'some@other.tld', 'zfCbzLm9h4$h');

        $I->see('error_de');
    }
}
