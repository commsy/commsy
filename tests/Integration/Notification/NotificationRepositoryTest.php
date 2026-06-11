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

    public function testFindLatestReturnsNewestFirstWithinLimit(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persist($account, sourceItemId: 1, createdAt: new \DateTimeImmutable('2026-06-01 10:00:00'), title: 'oldest');
        $this->persist($account, sourceItemId: 2, createdAt: new \DateTimeImmutable('2026-06-02 10:00:00'), title: 'middle');
        $this->persist($account, sourceItemId: 3, createdAt: new \DateTimeImmutable('2026-06-03 10:00:00'), title: 'newest');

        $latest = $this->repository()->findLatestForAccount($account, 2);

        self::assertCount(2, $latest);
        self::assertSame('newest', $latest[0]->getTitle());
        self::assertSame('middle', $latest[1]->getTitle());
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
        self::assertTrue($this->repository()->existsForSourceItem(99));
        self::assertFalse($this->repository()->existsForSourceItem(42));
    }

    public function testRemoveReadOlderThanKeepsUnreadAndRecent(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $old = $this->persist($account, sourceItemId: 1);
        $old->markRead(new \DateTimeImmutable('2026-01-01 00:00:00'));
        $this->repository()->save($old);

        $recentlyRead = $this->persist($account, sourceItemId: 2);
        $recentlyRead->markRead(new \DateTimeImmutable('2026-06-10 00:00:00'));
        $this->repository()->save($recentlyRead);

        $this->persist($account, sourceItemId: 3); // unread

        $removed = $this->repository()->removeReadOlderThan(new \DateTimeImmutable('2026-03-01 00:00:00'));

        self::assertSame(1, $removed);
        self::assertSame(2, $this->repository()->count([]));
    }

    public function testPaginationAndFilters(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $unread = $this->persist($account, sourceItemId: 1, contextId: 10, createdAt: new \DateTimeImmutable('2026-06-03 10:00:00'));
        $read = $this->persist($account, sourceItemId: 2, contextId: 20, createdAt: new \DateTimeImmutable('2026-06-02 10:00:00'));
        $read->markRead(new \DateTimeImmutable());
        $this->repository()->save($read);
        $this->persist($account, sourceItemId: 3, contextId: 10, createdAt: new \DateTimeImmutable('2026-06-01 10:00:00'));

        // First page, two per page, newest first.
        $page1 = $this->repository()->findForAccountPaginated($account, 1, 2);
        self::assertCount(2, $page1);
        self::assertSame(1, $page1[0]->getSourceItemId());

        // Unread-only filter.
        self::assertSame(2, $this->repository()->countForAccount($account, unreadOnly: true));

        // Context filter.
        self::assertSame(2, $this->repository()->countForAccount($account, contextId: 10));
        self::assertSame(3, $this->repository()->countForAccount($account));
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
            NotificationType::NewEntry,
            $contextId,
            $title,
            'Room',
            $createdAt ?? new \DateTimeImmutable('2026-06-01 10:00:00'),
            $sourceItemId,
            'material',
            'Actor',
        );
        $this->repository()->save($notification);

        return $notification;
    }
}
