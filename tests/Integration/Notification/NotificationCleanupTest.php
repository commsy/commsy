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

use App\Account\AccountDeleter;
use App\Cron\Tasks\CronCleanNotifications;
use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Retention and account-deletion cleanup: the cron prunes every notification
 * older than the retention window (read or not), and deleting an account removes
 * its notifications via the FK cascade.
 */
class NotificationCleanupTest extends KernelTestCase
{
    public function testCronPrunesNotificationsOlderThanRetentionRegardlessOfReadState(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persist($account, 1, new \DateTimeImmutable('-40 days'));                                          // pruned: old, unread
        $this->persist($account, 2, new \DateTimeImmutable('-40 days'), new \DateTimeImmutable('-39 days'));      // pruned: old, read
        $this->persist($account, 3, new \DateTimeImmutable('-10 days'));                                          // kept: recent, unread
        $this->persist($account, 4, new \DateTimeImmutable('-10 days'), new \DateTimeImmutable('-9 days'));       // kept: recent, read

        self::getContainer()->get(CronCleanNotifications::class)->run(null);

        self::assertSame(2, $this->repository()->count([]), 'everything older than the retention window is pruned, read or not');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeletingAccountRemovesItsNotifications(): void
    {
        self::bootKernel();
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');

        $this->repository()->save($this->newNotification($account, 1));
        self::assertSame(1, $this->repository()->count([]));

        self::getContainer()->get(AccountDeleter::class)->delete($account);

        self::assertSame(0, $this->repository()->count([]), 'deleting the account cascades its notifications away');
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }

    private function persist(Account $account, int $sourceItemId, \DateTimeImmutable $createdAt, ?\DateTimeImmutable $readAt = null): void
    {
        $notification = $this->newNotification($account, $sourceItemId, $createdAt);
        if ($readAt !== null) {
            $notification->markRead($readAt);
        }
        $this->repository()->save($notification);
    }

    private function newNotification(Account $account, int $sourceItemId, ?\DateTimeImmutable $createdAt = null): Notification
    {
        return new Notification(
            $account,
            NotificationType::Entry,
            10,
            'Title',
            'Room',
            $createdAt ?? new \DateTimeImmutable(),
            $sourceItemId,
            'material',
            null,
        );
    }
}
