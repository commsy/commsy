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

/**
 * Functional coverage for {@see \App\Controller\HelperController::portalEnter}.
 *
 * Note on fixtures: {@see \Tests\Factory\AccountFactory} runs the full
 * {@see \App\Facade\AccountCreatorFacade::persistNewAccount} afterPersist hook,
 * which (as a production side effect) auto-creates the account's private room
 * via the legacy user manager. This means every account from
 * {@see AccountStory} already has a private room, and the "no private room"
 * fallback branch is not exercisable through the story — covering it would
 * require a second account variant that bypasses the facade, which the project
 * does not currently need elsewhere.
 */
#[WithStory(AccountStory::class)]
class HelperControllerTest extends AbstractApplicationTestCase
{
    public function testPortalEnterWithPrivateRoomRedirectsToDashboard(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/enter");

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('~^/dashboard/\d+$~', $location);
    }

    public function testPortalEnterServerContextRedirectsToServerShow(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', '/portal/server/enter');

        $this->assertResponseRedirects('/portal/show');
    }

    public function testPortalEnterAsRootWithNumericContextRedirectsToListAll(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$account->getContextId()}/enter");

        $this->assertResponseRedirects("/room/{$account->getContextId()}/all");
    }

    public function testPortalEnterAnonymousIsRejected(): void
    {
        $this->client->request('GET', '/portal/1/enter');

        // IS_AUTHENTICATED triggers a redirect to the login page
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/login', $location);
    }
}
