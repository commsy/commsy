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
class MarkedControllerTest extends AbstractApplicationTestCase
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
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Mark-Raum');
    }

    public function testListPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/mark");

        $this->assertResponseIsSuccessful();
    }

    public function testFeedRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/mark/feed/0/date");

        $this->assertResponseIsSuccessful();
    }

    public function testXhrInsertAcceptsXml(): void
    {
        // With selectAll=true and no marked items in a fresh room, the action
        // iterates over an empty set and returns a JsonDataResponse — still
        // exercising the full controller path (getRoom, getItemsForActionRequest,
        // action->execute).
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/mark/xhr/insert",
            [
                'action' => 'insert',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testXhrRemoveAcceptsXml(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/mark/xhr/remove",
            [
                'action' => 'remove',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testXhrCategorizeAcceptsXml(): void
    {
        // Without payload.choices, handleCategoryActionOptions just renders the
        // category form partial as JsonHTMLResponse — exercising the render
        // branch of the controller.
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/mark/xhr/categorize",
            [
                'action' => 'categorize',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testXhrHashtagAcceptsXml(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/mark/xhr/hashtag",
            [
                'action' => 'hashtag',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testXhrInsertRejectsNonXhrRequest(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/mark/xhr/insert",
            [
                'action' => 'insert',
                'selectAll' => 'true',
            ]
        );

        // The XHR condition on the route means a plain POST cannot match it.
        $this->assertResponseStatusCodeSame(404);
    }
}
