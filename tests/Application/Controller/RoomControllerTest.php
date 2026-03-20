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
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(AccountStory::class)]
class RoomControllerTest extends AbstractApplicationTestCase
{
    use Factories;

    public function testHomeRouteForProjectRoom(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');

        $room = RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
            'type' => 'project',
        ]);

        $roomUser = RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
        ]);

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/room/{$room->getItemId()}");

        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testHomeRouteForCommunityRoom(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');

        $room = RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
            'type' => 'community',
        ]);

        $roomUser = RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
        ]);

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/room/{$room->getItemId()}");

        $this->assertResponseIsSuccessful();
    }
}
