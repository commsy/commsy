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
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Access control for the room RSS feed (/rss/{contextId}).
 *
 * Feeds exist only for rooms: server/portal contexts return 404, and a room
 * that is not open for guests requires a valid RSS hash (403 without one).
 */
#[WithStory(AccountStory::class)]
class FeedControllerTest extends AbstractApplicationTestCase
{
    use Factories;

    public function testServerContextHasNoFeed(): void
    {
        // The server context (99) has no feed of its own.
        $this->client->request('GET', '/rss/99');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonGuestRoomWithoutHashIsForbidden(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');

        $room = RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
            'type' => 'project',
        ]);

        // A project room is not open for guests by default; the RSS feed then
        // requires a valid hash. Without one the request must be forbidden.
        $this->client->request('GET', "/rss/{$room->getItemId()}");

        $this->assertResponseStatusCodeSame(403);
    }
}
