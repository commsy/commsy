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
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A bulk action acts on the posted entries one may see, and a changing
 * action only on those one may change. Posting the id of an entry from a
 * room one has no access to does nothing to it.
 */
#[WithStory(RoomWithMemberStory::class)]
final class BulkActionRoomBoundaryTest extends AbstractApplicationTestCase
{
    private Connection $connection;
    private Room $room;
    private Materials $own;
    private Materials $foreign;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = self::getContainer()->get(Connection::class);
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        // the account moderates its room
        $this->connection->update('user', ['status' => 3], ['item_id' => $roomUser->getItemId()]);
        $this->own = MaterialFactory::createOne(['room' => $this->room, 'creator' => $roomUser, 'title' => 'Eigenes Material']);

        $portal = $account->getPortal();
        $foreignRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $owner = RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $portal, 'authSource' => $account->getAuthSource()]),
            'room' => $foreignRoom,
            'status' => 3,
        ]);
        $this->foreign = MaterialFactory::createOne(['room' => $foreignRoom, 'creator' => $owner, 'title' => 'Fremdes Material']);

        $this->loginAsUser((int) $portal?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    public function testAForeignEntryCannotBeCopiedIntoTheRoom(): void
    {
        $this->action('material/xhr/mark', 'mark', $this->foreign);
        $this->action('mark/xhr/insert', 'insert', $this->foreign);

        self::assertSame(1, $this->materialsInTheRoom());
    }

    public function testAnOwnEntryCanStillBeCopied(): void
    {
        $this->action('material/xhr/mark', 'mark', $this->own);
        $this->action('mark/xhr/insert', 'insert', $this->own);

        self::assertSame(2, $this->materialsInTheRoom());
    }

    public function testAForeignEntryCannotBeDownloaded(): void
    {
        $this->client->request('POST', "/room/{$this->room->getItemId()}/material/download", [
            'action' => 'download',
            'positiveItemIds' => [$this->foreign->getItemId()],
        ]);

        // denied, which bounces to the room rather than failing
        self::assertContains($this->client->getResponse()->getStatusCode(), [302, 403]);
    }

    public function testAForeignEntryIsNeitherPinnedNorDeactivated(): void
    {
        $this->action('material/xhr/pin', 'pin', $this->foreign);
        $this->action('material/xhr/deactivate', 'deactivate', $this->foreign);

        $row = $this->connection->fetchAssociative('SELECT pinned, activation_date FROM items WHERE item_id = ?', [$this->foreign->getItemId()]);
        self::assertSame(0, (int) $row['pinned']);
        self::assertNull($row['activation_date']);
    }

    public function testAnOwnEntryIsStillPinned(): void
    {
        $this->action('material/xhr/pin', 'pin', $this->own);

        self::assertSame(1, (int) $this->connection->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$this->own->getItemId()]));
    }

    private function action(string $route, string $action, Materials $material): void
    {
        $this->client->xmlHttpRequest('POST', "/room/{$this->room->getItemId()}/{$route}", [
            'action' => $action,
            'positiveItemIds' => [$material->getItemId()],
        ]);
    }

    private function materialsInTheRoom(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM items WHERE context_id = ? AND type = ? AND deletion_date IS NULL',
            [$this->room->getItemId(), 'material'],
        );
    }
}
