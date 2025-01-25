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
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(PortalStory::class)]
class ShibbolethControllerTest extends AbstractApplicationTestCase
{
    public function testInitiatorUrlAccessDenied(): void
    {
        $portal = PortalStory::get('portal');

        // We assume that the default portal does not have a shibboleth source configured
        $this->client->request('GET', "/login/{$portal->getId()}/auth/shib");

        // The AccessDeniedException will result in a redirect to login page
        $this->assertResponseRedirects("/login/{$portal->getId()}");
    }

    public function testInitiatorUrlDefault(): void
    {
        $portal = PortalStory::get('portal');

        // Enable shibboleth auth
        $this->loginAsRoot();
        $crawler = $this->client->request('GET', "/portal/{$portal->getId()}/settings/auth/shib");
        $form = $crawler->selectButton('auth_shibboleth[save]')->form();
        $form['auth_shibboleth[enabled]']->tick();
        $form['auth_shibboleth[title]'] = 'Shibboleth';
        $form['auth_shibboleth[loginUrl]'] = 'https://example.com';
        $form['auth_shibboleth[mappingUsername]'] = 'eppn';
        $form['auth_shibboleth[mappingFirstname]'] = 'givenName';
        $form['auth_shibboleth[mappingLastname]'] = 'sn';
        $form['auth_shibboleth[mappingEmail]'] = 'mail';
        $this->client->submit($form);
        $this->logout();

        $this->client->request('GET', "/login/{$portal->getId()}/auth/shib");
        $this->assertResponseRedirects("https://example.com?target=http%3A%2F%2Flocalhost%2Flogin%2F{$portal->getId()}%2Fauth%2Fshib%2Fcheck");
    }
}
