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
 * Retention and account-deletion cleanup: the cron prunes only read, aged-out
 * notifications, and deleting an account removes its notifications via the FK
 * cascade.
 */
class NotificationCleanupTest extends KernelTestCase
{
    public function testCronPrunesOnlyReadNotificationsOlderThanRetention(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();

        $this->persistRead($account, 1, new \DateTimeImmutable('-200 days')); // pruned
        $this->persistRead($account, 2, new \DateTimeImmutable('-10 days'));   // kept: recently read
        $this->persistUnread($account, 3);                                     // kept: unread

        self::getContainer()->get(CronCleanNotifications::class)->run(null);

        self::assertSame(2, $this->repository()->count([]), 'only the aged-out read notification is pruned');
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

    private function persistRead(Account $account, int $sourceItemId, \DateTimeImmutable $readAt): void
    {
        $notification = $this->newNotification($account, $sourceItemId);
        $notification->markRead($readAt);
        $this->repository()->save($notification);
    }

    private function persistUnread(Account $account, int $sourceItemId): void
    {
        $this->repository()->save($this->newNotification($account, $sourceItemId));
    }

    private function newNotification(Account $account, int $sourceItemId): Notification
    {
        return new Notification(
            $account,
            NotificationType::NewEntry,
            10,
            'Title',
            'Room',
            new \DateTimeImmutable(),
            $sourceItemId,
            'material',
            null,
        );
    }
}
