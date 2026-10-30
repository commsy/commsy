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

declare(strict_types=1);

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Rooms are deleted through the lock-and-delete route, which checks the
 * right to delete the room itself. The project list of a room offers no
 * delete route of its own.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ProjectRoomDeleteRouteTest extends AbstractApplicationTestCase
{
    public function testTheProjectListOffersNoDeleteRoute(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        $other = RoomFactory::new()->project()->create(['contextId' => $account->getPortal()?->getId(), 'portal' => $account->getPortal()]);

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
        $this->client->request('GET', "/room/{$room->getItemId()}/project/{$other->getItemId()}/delete");

        self::assertResponseStatusCodeSame(404);
        self::assertNull(self::getContainer()->get(Connection::class)->fetchOne('SELECT deletion_date FROM room WHERE item_id = ?', [$other->getItemId()]));
    }
}
