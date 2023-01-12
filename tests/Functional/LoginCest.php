<?php

namespace Tests\Functional;

use App\Entity\AuthSourceLocal;
use App\Entity\Invitations;
use Tests\Support\FunctionalTester;
use Tests\Support\Page\Functional\PortalAuthLocal;
use Tests\Support\Page\Functional\Registration;
use Tests\Support\Page\Functional\Room;
use Tests\Support\Step\Functional\Root;
use Tests\Support\Step\Functional\User;

class LoginCest
{
    public function loginAsRoot(Root $R)
    {
        $R->loginAsRoot();

        $R->seeCurrentRouteIs('app_server_show');
    }

    public function loginAsUser(Root $R, User $U)
    {
        $R->loginAndCreatePortalAsRoot();
        $R->goToLogoutPath();

        $U->registerAndLoginAsUser(1);
        $U->seeCurrentRouteIs('app_dashboard_overview');

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

    public function register(Root $R, FunctionalTester $I, Registration $registrationPage)
    {
        $R->loginAndCreatePortalAsRoot();
        $R->goToLogoutPath();

        $registrationPage->register(1, 'Firstname', 'Lastname', 'username',
            'some@mail.test', 'zfCbzLm9h4$h');

        $I->seeCurrentRouteIs('app_login');
    }

    public function registerWithBadPassword(Root $R, FunctionalTester $I, Registration $registrationPage)
    {
        $R->loginAndCreatePortalAsRoot();
        $R->goToLogoutPath();

        $registrationPage->register(1, 'Firstname', 'Lastname', 'username',
            'some@mail.test', 'badpassword');

        $I->see('Das eingegebene Passwort muss mindestens einen Großbuchstaben enthalten');
        $I->see('Das eingegebene Passwort muss mindestens ein Sonderzeichen enthalten');
        $I->see('Das eingegebene Passwort muss mindestens eine Zahl enthalten');
        $I->see('Das Passwort muss mindestens 8 Zeichen lang sein und mindestens einen Klein- und Großbuchstaben, sowie ein Sonderzeichen und eine Zahl enthalten');
    }

    public function registerWithUnallowedEmail(
        Root $R,
        FunctionalTester $I,
        PortalAuthLocal $portalAuthLocalPage,
        Registration $registrationPage
    ) {
        $R->loginAndCreatePortalAsRoot();

        $portalAuthLocalPage->configure(1, true, 'Lokal', true, '~.*@domain.tld~');

        $R->amOnRoute('app_portalsettings_translations', [
            'portalId' => 1,
        ]);
        $R->click('Fehlermeldung E-Mail Validierung');
        $R->fillField('#translation_translationDe', 'error_de');
        $R->fillField('#translation_translationEn', 'error_en');
        $R->click('#translation_update');

        $R->goToLogoutPath();

        $registrationPage->register(1, 'Firstname', 'Lastname', 'username',
            'some@other.tld', 'zfCbzLm9h4$h');

        $I->see('error_de');
    }

    public function registerWithInvitation(
        Root $R,
        User $U,
        PortalAuthLocal $portalAuthLocalPage,
        Room $roomPage,
        Registration $registrationPage
    ) {
        $R->loginAndCreatePortalAsRoot();
        $R->goToLogoutPath();

        $U->registerAndLoginAsUser(1);
        $roomPage->create(1, 'Testraum');
        $roomId = $U->grabFromCurrentUrl('~^/portal/\d+/room/(\d+)~');

        // Create an inviation in the room settings
        $U->amOnRoute('app_settings_invitations', [
            'roomId' => $roomId,
        ]);
        $U->fillField('#invitations_settings_email', 'asdf@some.mail');
        $U->click('#invitations_settings_send');
        $U->seeResponseCodeIsSuccessful();
        $U->see('asdf@some.mail');

        $U->goToLogoutPath();

        $R->loginAsRoot();
        $portalAuthLocalPage->configure(1, true, 'Lokal', true, '', AuthSourceLocal::ADD_ACCOUNT_INVITE);
        $R->goToLogoutPath();

        /** @var Invitations $invitation */
        $invitation = $U->grabEntityFromRepository(Invitations::class, ['email' => 'asdf@some.mail']);
        $token = $invitation->getHash();

        // No token
        $U->amOnRoute('app_account_signup', [
            'id' => 1,
        ]);
        $U->see('Der Einladungslink ist nicht (mehr) gültig.');

        // Wrong token
        $U->amOnRoute('app_account_signup', [
            'id' => 1,
            'token' => 'invalid',
        ]);
        $U->see('Der Einladungslink ist nicht (mehr) gültig.');

        // Valid token
        $U->amOnRoute('app_account_signup', [
            'id' => 1,
            'token' => $token,
        ]);
        $U->dontSee('Der Einladungslink ist nicht (mehr) gültig.');
    }
}
