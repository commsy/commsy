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

use App\Entity\Account;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
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

    public function testLoginPageRendersForPortal(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getPortal()->getId();

        $this->client->request('GET', "/login/{$portalId}");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }


    public function testLogoutRedirects(): void
    {
        $this->client->request('GET', '/logout');

        // The logout firewall configuration redirects somewhere — to / or
        // to a portal-specific login page depending on the current
        // context. We just verify it's a redirect (i.e. we left /logout).
        $this->assertResponseRedirects();
    }

    public function testRequestAccountsPageRenders(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getPortal()->getId();

        $this->client->request('GET', "/login/{$portalId}/request_accounts");
        $this->assertResponseIsSuccessful();
    }

    public function testRequestPasswordResetPageRenders(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getPortal()->getId();

        $this->client->request('GET', "/login/{$portalId}/request_password_reset");
        $this->assertResponseIsSuccessful();
    }

    public function testPasswordResetWithUnknownTokenRedirectsToLogin(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getPortal()->getId();

        $this->client->request('GET', "/login/{$portalId}/password_reset/no-such-token-xyz");

        // unknown / expired reset tokens are bounced back to the login
        // page with a flash message rather than 404'd.
        $this->assertResponseRedirects("/login/{$portalId}");
    }

    public function testSimultaneousLoginPageRenders(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getPortal()->getId();

        $this->client->request('GET', "/login/{$portalId}/simultaneous");
        $this->assertResponseIsSuccessful();
    }

    public function testAdminPageRedirectsToLoginWhenAnonymous(): void
    {
        $this->client->request('GET', '/admin');
        // The Symfony firewall intercepts before the admin controller's
        // own redirect runs, so the user lands on /login (the firewall's
        // default login_path) rather than the controller's preferred
        // /login/server target.
        $this->assertResponseRedirects('/login');
    }
}
