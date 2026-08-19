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
use App\Enum\NotificationType;
use App\Notification\RoomMembershipNotifier;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Covers the join-request tasks: who gets one, that it is announced once, and
 * that deciding clears it for every moderator while telling the requester.
 */
#[WithStory(AccountStory::class)]
class RoomMembershipNotifierTest extends KernelTestCase
{
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->account = AccountStory::get('account');
    }

    public function testEveryModeratorGetsTheTaskButPlainMembersDoNot(): void
    {
        $room = $this->createRoom();
        $moderator = $this->member($room, $this->newAccount(), status: 3);
        $secondModerator = $this->member($room, $this->newAccount(), status: 3);
        $plainMember = $this->member($room, $this->newAccount(), status: 2);
        $requester = $this->member($room, $this->account, status: 1);

        $this->notifier()->requestReceived($room->getItemId(), $requester->getItemId(), 'Ada Lovelace');

        $rows = $this->repository()->findAll();
        self::assertCount(2, $rows, 'one task per moderator');

        $recipientIds = array_map(static fn (Notification $n): int => $n->getRecipient()->getId(), $rows);
        self::assertContains($moderator->getAccount()->getId(), $recipientIds);
        self::assertContains($secondModerator->getAccount()->getId(), $recipientIds);
        self::assertNotContains($plainMember->getAccount()->getId(), $recipientIds);

        $task = $rows[0];
        self::assertSame(NotificationType::RoomJoinRequest, $task->getType());
        self::assertSame('Ada Lovelace', $task->getTitle());
        self::assertSame($requester->getItemId(), $task->getSourceItemId(), 'the requester is the handle for deciding');
        self::assertSame($requester->getItemId(), $task->getPayload()->actorId);
        self::assertTrue($task->isUnread());
    }

    public function testTheSameRequestIsAnnouncedOnlyOnce(): void
    {
        $room = $this->createRoom();
        $this->member($room, $this->newAccount(), status: 3);
        $requester = $this->member($room, $this->account, status: 1);

        $this->notifier()->requestReceived($room->getItemId(), $requester->getItemId(), 'Ada');
        $this->notifier()->requestReceived($room->getItemId(), $requester->getItemId(), 'Ada');

        self::assertSame(1, $this->repository()->count([]));
    }

    public function testAcceptingClearsTheTaskAndTellsTheRequester(): void
    {
        $room = $this->createRoom();
        $this->member($room, $this->newAccount(), status: 3);
        $this->member($room, $this->newAccount(), status: 3);
        $requester = $this->member($room, $this->account, status: 1);

        $this->notifier()->requestReceived($room->getItemId(), $requester->getItemId(), 'Ada');
        self::assertSame(2, $this->repository()->count([]));

        $this->notifier()->requestDecided($room->getItemId(), $requester->getItemId(), accepted: true);

        $rows = $this->repository()->findAll();
        self::assertCount(1, $rows, 'both moderator tasks are resolved, one decision remains');

        $decision = $rows[0];
        self::assertSame(NotificationType::RoomJoinDecision, $decision->getType());
        self::assertSame($this->account->getId(), $decision->getRecipient()->getId());
        self::assertSame('accepted', $decision->getPayload()->decision);
        self::assertSame($room->getTitle(), $decision->getTitle());
    }

    public function testRejectingIsReportedAsSuch(): void
    {
        $room = $this->createRoom();
        $this->member($room, $this->newAccount(), status: 3);
        $requester = $this->member($room, $this->account, status: 1);

        $this->notifier()->requestReceived($room->getItemId(), $requester->getItemId(), 'Ada');
        $this->notifier()->requestDecided($room->getItemId(), $requester->getItemId(), accepted: false);

        $rows = $this->repository()->findAll();
        self::assertCount(1, $rows);
        self::assertSame('rejected', $rows[0]->getPayload()->decision);
    }

    public function testDecidingWithoutAnOpenRequestDoesNothing(): void
    {
        $room = $this->createRoom();
        $this->member($room, $this->newAccount(), status: 3);
        $requester = $this->member($room, $this->account, status: 2);

        // An ordinary status change must not produce a decision notification.
        $this->notifier()->requestDecided($room->getItemId(), $requester->getItemId(), accepted: true);

        self::assertSame(0, $this->repository()->count([]));
    }

    private function notifier(): RoomMembershipNotifier
    {
        return self::getContainer()->get(RoomMembershipNotifier::class);
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
}
