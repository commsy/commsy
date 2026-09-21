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
use App\Enum\EntryAction;
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

    public function testFanOutReachesEveryMemberAndPreReadsTheActorsOwnRow(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $bob = $this->member($room, $this->newAccount());
        $carol = $this->member($room, $this->newAccount());

        $this->manager()->notifyNewEntry(
            $this->signal($room, $creator, sourceItemId: 555, title: 'New material', type: 'material')
        );

        $rows = $this->repository()->findAll();
        self::assertCount(3, $rows, 'every member including the actor gets a row');

        $byRecipient = [];
        foreach ($rows as $row) {
            $byRecipient[$row->getRecipient()->getId()] = $row;
        }

        self::assertArrayHasKey($bob->getAccount()->getId(), $byRecipient);
        self::assertArrayHasKey($carol->getAccount()->getId(), $byRecipient);
        self::assertTrue($byRecipient[$bob->getAccount()->getId()]->isUnread(), 'other members see it as unread');

        $own = $byRecipient[$creator->getAccount()->getId()] ?? null;
        self::assertNotNull($own, 'the actor sees their own entry in the panel');
        self::assertFalse($own->isUnread(), "the actor's own row is stored already read");

        $row = $byRecipient[$bob->getAccount()->getId()];
        self::assertSame('New material', $row->getTitle());
        self::assertSame('material', $row->getSourceItemType());
        self::assertSame(555, $row->getSourceItemId());
        self::assertSame($room->getItemId(), $row->getContextId());
        self::assertSame($room->getTitle(), $row->getRoomTitle());
        self::assertSame('Creator Name', $row->getActorName());
        self::assertSame(EntryAction::Created, $row->getAction());
    }

    public function testEditAtNewTimestampLogsAnotherEvent(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $this->member($room, $this->newAccount());

        $created = $this->signal($room, $creator, sourceItemId: 888, occurredAt: new \DateTimeImmutable('2026-06-15 12:00:00'));
        $edited = $this->signal($room, $creator, sourceItemId: 888, action: EntryAction::Edited, occurredAt: new \DateTimeImmutable('2026-06-16 09:30:00'));

        $this->manager()->notifyNewEntry($created);
        $this->manager()->notifyNewEntry($edited);

        $rows = $this->repository()->findAll();

        // Two events, each fanned out to the actor plus the other member.
        $eventTimes = array_unique(array_map(static fn (Notification $n): string => $n->getCreatedAt()->format('c'), $rows));
        self::assertCount(2, $eventTimes, 'an edit at a new time is logged as a second event');

        $actions = array_map(static fn (Notification $n): EntryAction => $n->getAction(), $rows);
        self::assertContains(EntryAction::Created, $actions);
        self::assertContains(EntryAction::Edited, $actions);
    }

    public function testEditByNonCreatorLeavesTheCreatorUnreadAndTheEditorRead(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);       // owns the entry
        $editor = $this->member($room, $this->newAccount());   // a different member edits it

        // The actor is the editor, not the item's creator.
        $this->manager()->notifyNewEntry($this->signal(
            $room,
            $creator,
            sourceItemId: 999,
            action: EntryAction::Edited,
            actor: $editor,
        ));

        $byRecipient = [];
        foreach ($this->repository()->findAll() as $row) {
            $byRecipient[$row->getRecipient()->getId()] = $row;
        }

        $creatorRow = $byRecipient[$creator->getAccount()->getId()] ?? null;
        self::assertNotNull($creatorRow, 'the creator is notified about the edit');
        self::assertTrue($creatorRow->isUnread(), "someone else's edit is unread for the creator");

        $editorRow = $byRecipient[$editor->getAccount()->getId()] ?? null;
        self::assertNotNull($editorRow, 'the editor still sees the entry in their own panel');
        self::assertFalse($editorRow->isUnread(), 'the editor who caused the event is not alerted');
    }

    public function testRepublishDoesNotDuplicate(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $this->member($room, $this->newAccount());

        $signal = $this->signal($room, $creator, sourceItemId: 777);
        $this->manager()->notifyNewEntry($signal);
        $this->manager()->notifyNewEntry($signal);

        // One fan-out only: the other member plus the actor's own row, not twice over.
        self::assertSame(2, $this->repository()->count([]), 're-publish must be idempotent');
    }

    public function testFanOutSnapshotsThePayload(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $this->member($room, $this->newAccount());

        $this->manager()->notifyNewEntry($this->signal(
            $room,
            $creator,
            sourceItemId: 321,
            payload: ['creatorName' => 'Ada Lovelace', 'hasAttachments' => true],
        ));

        $row = $this->repository()->findAll()[0];
        self::assertSame('Ada Lovelace', $row->getPayload()->creatorName);
        self::assertTrue($row->getPayload()->hasAttachments);
    }

    public function testRoomWithOnlyTheCreatorAlertsNobody(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);

        $this->manager()->notifyNewEntry($this->signal($room, $creator, sourceItemId: 1));

        self::assertSame(1, $this->repository()->count([]), 'the creator still sees their own entry');
        self::assertSame(0, $this->repository()->countUnreadForAccount($creator->getAccount()), 'but nothing is unread');
    }

    public function testPendingAndRejectedMembersAreNotNotified(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $confirmed = $this->member($room, $this->newAccount());
        $this->member($room, $this->newAccount(), status: 1); // pending request
        $this->member($room, $this->newAccount(), status: 0); // rejected

        $this->manager()->notifyNewEntry($this->signal($room, $creator, sourceItemId: 2));

        $recipientIds = array_map(
            static fn (Notification $n): int => $n->getRecipient()->getId(),
            $this->repository()->findAll()
        );

        // Only the confirmed member and the actor themselves; pending/rejected get nothing.
        self::assertCount(2, $recipientIds);
        self::assertContains($confirmed->getAccount()->getId(), $recipientIds);
        self::assertContains($creator->getAccount()->getId(), $recipientIds);
    }

    public function testDeactivatedEntryNotifiesModeratorsButNotRegularMembers(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($room, $this->account);
        $this->member($room, $this->newAccount());                 // regular member (status 2)
        $moderator = $this->member($room, $this->newAccount(), status: 3);

        // A not-yet-activated entry is only visible to moderators and the
        // creator; ITEM_SEE must keep it from the regular member.
        $this->manager()->notifyNewEntry(
            $this->signal($room, $creator, sourceItemId: 4, isDeactivated: true)
        );

        $recipientIds = array_map(
            static fn (Notification $n): int => $n->getRecipient()->getId(),
            $this->repository()->findAll()
        );

        self::assertCount(2, $recipientIds);
        self::assertContains($moderator->getAccount()->getId(), $recipientIds);
        self::assertContains($creator->getAccount()->getId(), $recipientIds, 'the creator sees their own deactivated entry');
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
        bool $isDeactivated = false,
        EntryAction $action = EntryAction::Created,
        ?\DateTimeImmutable $occurredAt = null,
        array $payload = [],
        ?User $actor = null,
    ): NotifyNewEntryMessage {
        return new NotifyNewEntryMessage(
            $sourceItemId,
            $room->getItemId(),
            $type,
            $title,
            $creator->getItemId(),
            ($actor ?? $creator)->getItemId(),
            'Creator Name',
            $isDeactivated,
            $action,
            $occurredAt ?? new \DateTimeImmutable('2026-06-15 12:00:00'),
            $payload,
        );
    }
}
