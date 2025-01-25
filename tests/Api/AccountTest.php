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

class AccountTest extends AbstractApiTestCase
{
    #[WithStory(AccountStory::class)]
    public function testCheckLocalLoginIncorrectPayload(): void
    {
        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('POST', '/api/v2/accounts/checkLocalLogin', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        //$this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertResponseStatusCodeSame(400);
    }

    #[WithStory(AccountStory::class)]
    public function testCheckLocalLoginWrongCredentials(): void
    {
        $account = AccountStory::get('account');

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('POST', '/api/v2/accounts/checkLocalLogin', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => [
                'contextId' => $account->getContextId(),
                'username' => 'notauser',
                'password' => 'somepassword',
            ]
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    #[WithStory(AccountStory::class)]
    public function checkLocalLoginValidCredentials(): void
    {
        $portal = AccountStory::get('portal');
        $account = AccountStory::get('account');

        $client = $this->createClientWithCredentials($this->getReadOnlyToken());
        $client->request('POST', '/api/v2/accounts/checkLocalLogin', [
            'json' => [
                'contextId' => $portal->getId(),
                'username' => $account->getUsername(),
                'password' => $account->getPlainPassword(),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema([
            'id' => 'integer',
            'username' => 'string',
            'firstname' => 'string',
            'lastname' => 'string',
            'email' => 'string',
            'locked' => 'boolean',
        ]);

        $this->assertJsonContains([
            'id' => $account->getId(),
            'username' => $account->getUsername(),
            'firstname' => $account->getFirstname(),
            'lastname' => $account->getLastname(),
            'email' => $account->getEmail(),
            'locked' => $account->isLocked(),
        ]);
    }

    #[WithStory(AccountStory::class)]
    public function getWorkspaces(): void
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
    }
}
