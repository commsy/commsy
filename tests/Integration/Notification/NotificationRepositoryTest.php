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
use App\Enum\EntryAction;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;

/**
 * Exercises the notification persistence and the read/list/cleanup queries
 * against the real schema (migrate-mode reset via Foundry).
 */
class NotificationRepositoryTest extends KernelTestCase
{
    public function testCountUnreadReflectsReadState(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $first = $this->persist($account, sourceItemId: 1);
        $this->persist($account, sourceItemId: 2);

        self::assertSame(2, $this->repository()->countUnreadForAccount($account));

        $first->markRead(new \DateTimeImmutable());
        $this->repository()->save($first);

        self::assertSame(1, $this->repository()->countUnreadForAccount($account));
    }

    public function testCountUnreadIsScopedToRecipient(): void
    {
        self::bootKernel();
        $mine = AccountFactory::createOne();
        $other = AccountFactory::createOne();

        $this->persist($mine, sourceItemId: 1);
        $this->persist($other, sourceItemId: 1);

        self::assertSame(1, $this->repository()->countUnreadForAccount($mine));
        self::assertSame(1, $this->repository()->countUnreadForAccount($other));
    }

    public function testMarkAllReadForAccountTouchesOnlyOwnUnread(): void
    {
        self::bootKernel();
        $mine = AccountFactory::createOne();
        $other = AccountFactory::createOne();

        $this->persist($mine, sourceItemId: 1);
        $this->persist($mine, sourceItemId: 2);
        $this->persist($other, sourceItemId: 1);

        $updated = $this->repository()->markAllReadForAccount($mine, new \DateTimeImmutable());

        self::assertSame(2, $updated);
        self::assertSame(0, $this->repository()->countUnreadForAccount($mine));
        self::assertSame(1, $this->repository()->countUnreadForAccount($other), 'other account stays untouched');
    }

    public function testMarkAllReadCanBeScopedToOneRoom(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persist($account, sourceItemId: 1, contextId: 10);
        $this->persist($account, sourceItemId: 2, contextId: 20);

        $updated = $this->repository()->markAllReadForAccount($account, new \DateTimeImmutable(), 10);

        self::assertSame(1, $updated);
        self::assertSame(0, $this->repository()->countUnreadForAccount($account, 10));
        self::assertSame(1, $this->repository()->countUnreadForAccount($account, 20), 'the other room stays unread');
    }

    public function testRemoveForSourceItemDropsAllRecipientsOfThatItem(): void
    {
        self::bootKernel();
        $a = AccountFactory::createOne();
        $b = AccountFactory::createOne();

        $this->persist($a, sourceItemId: 42);
        $this->persist($b, sourceItemId: 42);
        $this->persist($a, sourceItemId: 99);

        $removed = $this->repository()->removeForSourceItem(42);

        self::assertSame(2, $removed);
        self::assertSame(1, $this->repository()->count([]));
        self::assertCount(1, $this->repository()->findBy(['sourceItemId' => 99]));
        self::assertCount(0, $this->repository()->findBy(['sourceItemId' => 42]));
    }

    public function testFindForAccountNewestFirstWithContextFilter(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persist($account, sourceItemId: 1, contextId: 10, createdAt: new \DateTimeImmutable('2026-06-01 10:00:00'), title: 'old-10');
        $this->persist($account, sourceItemId: 2, contextId: 20, createdAt: new \DateTimeImmutable('2026-06-02 10:00:00'), title: 'mid-20');
        $this->persist($account, sourceItemId: 3, contextId: 10, createdAt: new \DateTimeImmutable('2026-06-03 10:00:00'), title: 'new-10');

        $all = $this->repository()->findForAccount($account);
        self::assertSame(['new-10', 'mid-20', 'old-10'], array_map(static fn ($n) => $n->getTitle(), $all));

        $room10 = $this->repository()->findForAccount($account, contextId: 10);
        self::assertSame(['new-10', 'old-10'], array_map(static fn ($n) => $n->getTitle(), $room10));
    }

    public function testRemoveOlderThanDeletesRegardlessOfReadState(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persist($account, sourceItemId: 1, createdAt: new \DateTimeImmutable('2026-01-01 00:00:00')); // old, unread
        $oldRead = $this->persist($account, sourceItemId: 2, createdAt: new \DateTimeImmutable('2026-01-02 00:00:00'));
        $oldRead->markRead(new \DateTimeImmutable('2026-01-03 00:00:00'));
        $this->repository()->save($oldRead);
        $this->persist($account, sourceItemId: 3, createdAt: new \DateTimeImmutable('2026-06-10 00:00:00')); // recent

        $removed = $this->repository()->removeOlderThan(new \DateTimeImmutable('2026-03-01 00:00:00'));

        self::assertSame(2, $removed, 'both old rows go regardless of read state');
        self::assertSame(1, $this->repository()->count([]));
    }

    public function testCountUnreadCanScopeToContext(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persist($account, sourceItemId: 1, contextId: 10);
        $this->persist($account, sourceItemId: 2, contextId: 20);

        self::assertSame(2, $this->repository()->countUnreadForAccount($account));
        self::assertSame(1, $this->repository()->countUnreadForAccount($account, 10));
    }

    public function testMarkReadForAccountAndSourceItemTouchesOnlyThatItemAndAccount(): void
    {
        self::bootKernel();
        $mine = AccountFactory::createOne();
        $other = AccountFactory::createOne();

        $this->persist($mine, sourceItemId: 42);   // unread, item 42
        $this->persist($mine, sourceItemId: 42);   // a second event for item 42 (e.g. an edit)
        $this->persist($mine, sourceItemId: 99);    // a different item
        $this->persist($other, sourceItemId: 42);   // another account, same item

        $marked = $this->repository()->markReadForAccountAndSourceItem($mine, 42, new \DateTimeImmutable());

        self::assertSame(2, $marked, 'both of my events for item 42 are marked read');
        self::assertSame(1, $this->repository()->countUnreadForAccount($mine), 'my item 99 stays unread');
        self::assertSame(1, $this->repository()->countUnreadForAccount($other), 'the other account is untouched');
    }

    public function testOpeningAnItemNeverMarksAnOpenTaskRead(): void
    {
        self::bootKernel();
        $moderator = AccountFactory::createOne();

        // A join request carries the requesting person's user entry as its
        // source item, so viewing that profile must not settle the request.
        $this->repository()->save(new Notification(
            $moderator,
            NotificationType::RoomJoinRequest,
            5,
            'Someone wants in',
            'Room',
            new \DateTimeImmutable(),
            180,
            'user',
            'Someone',
        ));
        $this->persist($moderator, sourceItemId: 180);

        $marked = $this->repository()->markReadForAccountAndSourceItem($moderator, 180, new \DateTimeImmutable());

        self::assertSame(1, $marked, 'only the entry activity is marked read');
        self::assertSame(1, $this->repository()->countUnreadForAccount($moderator), 'the request still counts');
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }

    private function persist(
        Account $recipient,
        int $sourceItemId,
        int $contextId = 5,
        string $title = 'Title',
        ?\DateTimeImmutable $createdAt = null,
    ): Notification {
        $notification = new Notification(
            $recipient,
            NotificationType::Entry,
            $contextId,
            $title,
            'Room',
            $createdAt ?? new \DateTimeImmutable('2026-06-01 10:00:00'),
            $sourceItemId,
            'material',
            'Actor',
            EntryAction::Created,
        );
        $this->repository()->save($notification);

        return $notification;
    }
}
