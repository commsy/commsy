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
class StepControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser(
            $this->account->getContextId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Testraum');
    }

    public function testXhrDeleteAcceptsXmlHttpRequest(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/step/xhr/delete",
            [
                'action' => 'delete',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testXhrDeleteRejectsNonXhrRequest(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/step/xhr/delete",
            [
                'action' => 'delete',
                'selectAll' => 'true',
            ]
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testXhrChangeStatusRejectsNonXhrRequest(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/step/xhr/changestatus/1",
            [
                'payload' => ['status' => 'done'],
            ]
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
