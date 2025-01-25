<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Tests\Application\Controller;

use Tests\Application\AbstractApplicationTestCase;

class SecurityControllerTest extends AbstractApplicationTestCase
{
    public function testLoginAsRoot(): void
    {
        $this->client->request('GET', '/login/server');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Login Systemadministration');

        $this->client->submitForm('login_local', [
            'email' => 'root',
            'password' => 'pcxEmQj6QzE5',
        ]);
        $this->assertResponseRedirects('/portal/server/enter');
    }

    public function atestLoginAsUser(): void
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
}
