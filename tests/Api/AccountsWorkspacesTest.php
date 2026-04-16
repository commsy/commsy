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

namespace Tests\Api;

use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class AccountsWorkspacesTest extends AbstractApiTestCase
{
    public function testGetAccountsWorkspaces(): void
    {
        $account = AccountStory::get('account');

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('GET', "/api/v2/accounts/{$account->getId()}/workspaces", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'type' => 'array',
        ]);
    }
}
