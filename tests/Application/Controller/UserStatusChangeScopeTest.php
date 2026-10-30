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
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The status change and the mail of the person list act on profiles of
 * their own room only. Profiles of other rooms and of the portal are left
 * as they are, even when their ids are posted.
 */
#[WithStory(RoomWithMemberStory::class)]
final class UserStatusChangeScopeTest extends AbstractApplicationTestCase
{
    private Connection $connection;
    private Account $account;
    private Room $room;
    private User $member;
    private User $ownProfileElsewhere;
    private User $strangerElsewhere;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = self::getContainer()->get(Connection::class);
        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');

        // the account moderates its room, which has a second member
        $this->connection->update('user', ['status' => 3], ['item_id' => RoomWithMemberStory::get('roomUser')->getItemId()]);
        $this->member = RoomUserFactory::createOne(['account' => $this->otherAccount(), 'room' => $this->room, 'status' => 2]);

        // in another room the account is a mere member, next to a stranger
        $elsewhere = RoomFactory::new()->project()->create(['contextId' => $this->account->getPortal()?->getId(), 'portal' => $this->account->getPortal()]);
        $this->ownProfileElsewhere = RoomUserFactory::createOne(['account' => $this->account, 'room' => $elsewhere, 'status' => 2]);
        $this->strangerElsewhere = RoomUserFactory::createOne(['account' => $this->otherAccount(), 'room' => $elsewhere, 'status' => 2]);

        $this->loginAsUser((int) $this->account->getPortal()?->getId(), $this->account->getUsername(), $this->account->getPlainPassword());
    }

    public function testOnlyProfilesOfTheRoomChangeTheirStatus(): void
    {
        $portalProfile = (int) $this->connection->fetchOne(
            'SELECT item_id FROM user WHERE account_id = ? AND context_id = ? AND deletion_date IS NULL',
            [$this->account->getId(), $this->account->getPortal()?->getId()],
        );

        $this->changeStatus('user-status-moderator', [$this->member->getItemId(), $this->ownProfileElsewhere->getItemId(), $portalProfile]);

        self::assertSame(3, $this->statusOf($this->member->getItemId()));
        self::assertSame(2, $this->statusOf($this->ownProfileElsewhere->getItemId()));
        self::assertSame(2, $this->statusOf($portalProfile));
    }

    public function testAProfileOfAnotherRoomIsNotBlocked(): void
    {
        $this->changeStatus('user-block', [$this->strangerElsewhere->getItemId()]);

        self::assertSame(2, $this->statusOf($this->strangerElsewhere->getItemId()));
    }

    public function testTheMailOfTheRoomAddressesOnlyItsProfiles(): void
    {
        $selected = $this->recipientsOf(['action' => 'sendmail', 'positiveItemIds' => [$this->member->getItemId(), $this->strangerElsewhere->getItemId()]]);
        $posted = $this->recipientsOf(['user_send' => ['users' => [$this->member->getItemId(), $this->strangerElsewhere->getItemId()]]]);

        self::assertSame([(string) $this->member->getItemId()], $selected);
        self::assertSame([(string) $this->member->getItemId()], $posted);
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<string> the profiles the mail form addresses
     */
    private function recipientsOf(array $parameters): array
    {
        $crawler = $this->client->request('POST', "/room/{$this->room->getItemId()}/user/sendMultiple", $parameters);
        // a form posted without its token renders again as invalid
        self::assertContains($this->client->getResponse()->getStatusCode(), [200, 422]);

        return $crawler->filter('input[name^="user_send[users]"]')->each(static fn ($input): string => (string) $input->attr('value'));
    }

    /**
     * @param list<int> $userIds
     */
    private function changeStatus(string $status, array $userIds): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/changeStatus", [
            'userDetail' => 1,
            'status' => $status,
            'userIds' => $userIds,
        ]);
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('user_status_save', ['user_status[inform_user]' => '0']);
    }

    private function statusOf(int $itemId): int
    {
        return (int) $this->connection->fetchOne('SELECT status FROM user WHERE item_id = ?', [$itemId]);
    }

    private function otherAccount(): Account
    {
        return AccountFactory::createOne(['portal' => $this->account->getPortal(), 'authSource' => $this->account->getAuthSource()]);
    }
}
