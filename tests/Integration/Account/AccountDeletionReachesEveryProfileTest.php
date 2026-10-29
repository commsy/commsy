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

namespace Tests\Integration\Account;

use App\Account\AccountDeleter;
use App\Entity\Account;
use App\Entity\Room;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Deleting an account ends every profile of it, not only those its portal
 * profile leads to, and deletes every private room it owns. A profile left
 * alive loses its account on the deletion and then blocks the next account
 * with the same username.
 */
final class AccountDeletionReachesEveryProfileTest extends KernelTestCase
{
    #[WithStory(RoomWithMemberStory::class)]
    public function testASecondPrivateRoomIsDeletedWithItsOwner(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $ownPrivateRoomId = $this->privateRoomIdsOf($account);
        $secondPrivateRoom = $this->secondPrivateRoomOf($account);

        self::getContainer()->get(AccountDeleter::class)->delete($account);

        foreach ([...$ownPrivateRoomId, $secondPrivateRoom->getItemId()] as $privateRoomId) {
            self::assertNotNull(
                $this->connection()->fetchOne('SELECT deletion_date FROM room WHERE item_id = ?', [$privateRoomId]),
                sprintf('private room %d is still alive', $privateRoomId),
            );
        }
        self::assertSame(0, (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM user WHERE context_id = ? AND deletion_date IS NULL',
            [$secondPrivateRoom->getItemId()],
        ));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheUsernameCanBeRegisteredAgainAfterTheDeletion(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->secondPrivateRoomOf($account);
        $username = $account->getUsername();
        $portal = $account->getPortal();
        $authSource = $account->getAuthSource();

        self::getContainer()->get(AccountDeleter::class)->delete($account);
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $again = AccountFactory::createOne([
            'username' => $username,
            'portal' => $portal,
            'authSource' => $authSource,
        ]);

        self::assertNotNull($again->getId());
    }

    private function secondPrivateRoomOf(Account $account): Room
    {
        $room = RoomFactory::new()->privateRoom()->create([
            'contextId' => $account->getPortal()->getId(),
            'portal' => $account->getPortal(),
        ]);
        RoomUserFactory::new()->asModerator()->create([
            'account' => $account,
            'room' => $room,
        ]);

        return $room;
    }

    /**
     * @return list<int>
     */
    private function privateRoomIdsOf(Account $account): array
    {
        return array_map('intval', $this->connection()->fetchFirstColumn(
            "SELECT r.item_id FROM room r JOIN user u ON u.context_id = r.item_id
              WHERE r.type = 'privateroom' AND u.account_id = ? AND u.deletion_date IS NULL",
            [$account->getId()],
        ));
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
