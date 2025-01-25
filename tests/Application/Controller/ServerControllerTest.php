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

class ServerControllerTest extends AbstractApplicationTestCase
{
    public function testCreatePortal(): void
    {
        $this->loginAsRoot();

        $this->client->request('GET', '/portal/show');

        $this->client->clickLink('Portal erstellen');
        $this->assertRouteSame('app_server_createportal');

        $this->client->submitForm('portal_general[save]', [
            'portal_general[title]' => 'Testportal',
            'portal_general[descriptionGerman]' => 'Deutsche Beschreibung',
            'portal_general[descriptionEnglish]' => 'English Description',
        ]);

        $this->client->followRedirect();
        $this->assertRouteSame('app_server_show');
        $this->assertAnySelectorTextContains('a', 'Testportal');
    }
}
