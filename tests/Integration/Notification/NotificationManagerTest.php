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

namespace Tests\Integration\Notification;

use App\Entity\Account;
use App\Entity\Notification;
use App\Entity\Room;
use App\Entity\User;
use App\Message\NotifyNewEntryMessage;
use App\Notification\NotificationManager;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Covers the new-entry fan-out: who receives a notification, idempotency on
 * re-publish, and the snapshotted payload.
 */
#[WithStory(AccountStory::class)]
class NotificationManagerTest extends KernelTestCase
{
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->account = AccountStory::get('account');
    }

    public function testFanOutCreatesOneUnreadRowPerMemberExceptCreator(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $bob = $this->member($room, $this->newAccount());
        $carol = $this->member($room, $this->newAccount());

        $this->manager()->notifyNewEntry(
            $this->signal($room, $creator, sourceItemId: 555, title: 'New material', type: 'material')
        );

        $rows = $this->repository()->findAll();
        self::assertCount(2, $rows);

        $recipientIds = array_map(static fn (Notification $n): int => $n->getRecipient()->getId(), $rows);
        self::assertContains($bob->getAccount()->getId(), $recipientIds);
        self::assertContains($carol->getAccount()->getId(), $recipientIds);
        self::assertNotContains($creator->getAccount()->getId(), $recipientIds, 'author must not be notified');

        $row = $rows[0];
        self::assertTrue($row->isUnread());
        self::assertSame('New material', $row->getTitle());
        self::assertSame('material', $row->getSourceItemType());
        self::assertSame(555, $row->getSourceItemId());
        self::assertSame($room->getItemId(), $row->getContextId());
        self::assertSame($room->getTitle(), $row->getRoomTitle());
        self::assertSame('Creator Name', $row->getActorName());
    }

    public function testRepublishDoesNotDuplicate(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $this->member($room, $this->newAccount());

        $signal = $this->signal($room, $creator, sourceItemId: 777);
        $this->manager()->notifyNewEntry($signal);
        $this->manager()->notifyNewEntry($signal);

        self::assertSame(1, $this->repository()->count([]), 're-publish must be idempotent');
    }

    public function testRoomWithOnlyTheCreatorNotifiesNobody(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);

        $this->manager()->notifyNewEntry($this->signal($room, $creator, sourceItemId: 1));

        self::assertSame(0, $this->repository()->count([]));
    }

    public function testPendingAndRejectedMembersAreNotNotified(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $confirmed = $this->member($room, $this->newAccount());
        $this->member($room, $this->newAccount(), status: 1); // pending request
        $this->member($room, $this->newAccount(), status: 0); // rejected

        $this->manager()->notifyNewEntry($this->signal($room, $creator, sourceItemId: 2));

        $rows = $this->repository()->findAll();
        self::assertCount(1, $rows);
        self::assertSame($confirmed->getAccount()->getId(), $rows[0]->getRecipient()->getId());
    }

    private function manager(): NotificationManager
    {
        return self::getContainer()->get(NotificationManager::class);
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }

    private function createRoom(): Room
    {
        $portal = $this->account->getPortal();

        return RoomFactory::new()->project()->create([
            'contextId' => $portal?->getId(),
            'portal' => $portal,
        ]);
    }

    private function newAccount(): Account
    {
        $portal = $this->account->getPortal();

        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
    }

    private function member(Room $room, Account $account, int $status = 2): User
    {
        return RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => $status,
        ]);
    }

    private function signal(
        Room $room,
        User $creator,
        int $sourceItemId,
        string $title = 'Title',
        string $type = 'material',
    ): NotifyNewEntryMessage {
        return new NotifyNewEntryMessage(
            $sourceItemId,
            $room->getItemId(),
            $type,
            $title,
            $creator->getItemId(),
            'Creator Name',
        );
    }
}
