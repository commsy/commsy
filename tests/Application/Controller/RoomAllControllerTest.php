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
class RoomAllControllerTest extends AbstractApplicationTestCase
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

    public function testRoomDetailRendersForMember(): void
    {
        $this->client->request('GET', "/portal/{$this->account->getContextId()}/room/{$this->roomId}");

        $this->assertResponseIsSuccessful();
    }

    public function testRoomDetailRejectsNonExistingRoom(): void
    {
        $nonExistingId = PHP_INT_MAX;
        $this->client->request('GET', "/portal/{$this->account->getContextId()}/room/{$nonExistingId}");

        // The ITEM_SEE voter runs before the controller and denies access for
        // non-existing rooms. Depending on the registered access denied handler
        // this surfaces as either a 3xx redirect or a 4xx response — but never
        // as a successful render.
        $status = $this->client->getResponse()->getStatusCode();
        self::assertNotSame(200, $status);
        self::assertGreaterThanOrEqual(300, $status);
    }
}
