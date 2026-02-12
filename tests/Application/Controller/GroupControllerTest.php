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
class GroupControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser($this->account->getContextId(), $this->account->getUsername(), $this->account->getPlainPassword());
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Testportal');
    }

    public function testCreate(): void
    {
        $this->client->request('GET', "/room/$this->roomId/group/create");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDetail(): void
    {
        $this->client->request('GET', "/room/$this->roomId/group/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/group/$itemId");
        $this->assertResponseIsSuccessful();

        // Forbidden
        $this->logout();
        $this->client->request('GET', "/room/$this->roomId/group/$itemId");
        $this->assertResponseRedirects("/login/{$this->account->getContextId()}");
    }

    public function testEdit(): void
    {
        $this->client->request('GET', "/room/$this->roomId/group/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/group/$itemId/edit");
        $this->assertResponseIsSuccessful();
    }

    public function testFeed(): void
    {
        $this->client->request('GET', "/room/$this->roomId/group/feed/0/date");
        $this->assertResponseIsSuccessful();
    }

    public function testList(): void
    {
        $this->client->request('GET', "/room/$this->roomId/group");
        $this->assertResponseIsSuccessful();
    }
}
