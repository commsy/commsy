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
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

class PortalSettingsControllerTest extends AbstractApplicationTestCase
{
    #[WithStory(PortalStory::class)]
    public function testRoomTermsTemplates(): void
    {
        $portal = PortalStory::get('portal');

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portal->getId()}/settings/contents/roomTermsTemplates");
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h3', 'Vorlagen Nutzungsbedingungen');
    }

    public function testAccountIndex(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portalId}/settings/accountindex");
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('a', "{$account->getFirstname()} {$account->getLastname()}");
        $this->assertAnySelectorTextContains('a', $account->getEmail());
    }

    public function testAccountIndexDetail(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portalId}/settings/accountindex");
        $this->client->clickLink("{$account->getFirstname()} {$account->getLastname()}");

        $this->assertResponseIsSuccessful();
    }
}
