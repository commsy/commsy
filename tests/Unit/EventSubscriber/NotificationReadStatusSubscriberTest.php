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

namespace Tests\Unit\EventSubscriber;

use App\Entity\Account;
use App\Entity\User;
use App\Enum\ReaderStatus;
use App\Event\ReadStatusPreChangeEvent;
use App\EventSubscriber\NotificationReadStatusSubscriber;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class NotificationReadStatusSubscriberTest extends TestCase
{
    public function testMarksNotificationsReadForTheViewedItem(): void
    {
        $account = $this->createMock(Account::class);
        $user = $this->createMock(User::class);
        $user->method('getAccount')->willReturn($account);

        $users = $this->createMock(UserRepository::class);
        $users->method('findOneBy')->with(['itemId' => 7])->willReturn($user);

        $notifications = $this->createMock(NotificationRepository::class);
        $notifications->expects($this->once())
            ->method('markReadForAccountAndSourceItem')
            ->with($account, 55, $this->isInstanceOf(\DateTimeImmutable::class));

        $subscriber = new NotificationReadStatusSubscriber($notifications, $users);
        $subscriber->onReadStatusPreChange(new ReadStatusPreChangeEvent(7, 55, ReaderStatus::STATUS_SEEN));
    }

    public function testIgnoresNonSeenStatusChanges(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects($this->never())->method('findOneBy');

        $notifications = $this->createMock(NotificationRepository::class);
        $notifications->expects($this->never())->method('markReadForAccountAndSourceItem');

        $subscriber = new NotificationReadStatusSubscriber($notifications, $users);
        $subscriber->onReadStatusPreChange(new ReadStatusPreChangeEvent(7, 55, ReaderStatus::STATUS_CHANGED));
    }

    public function testIgnoresUsersWithoutAccount(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAccount')->willReturn(null);

        $users = $this->createMock(UserRepository::class);
        $users->method('findOneBy')->willReturn($user);

        $notifications = $this->createMock(NotificationRepository::class);
        $notifications->expects($this->never())->method('markReadForAccountAndSourceItem');

        $subscriber = new NotificationReadStatusSubscriber($notifications, $users);
        $subscriber->onReadStatusPreChange(new ReadStatusPreChangeEvent(7, 55, ReaderStatus::STATUS_SEEN));
    }

    public function testIgnoresUnknownUser(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('findOneBy')->willReturn(null);

        $notifications = $this->createMock(NotificationRepository::class);
        $notifications->expects($this->never())->method('markReadForAccountAndSourceItem');

        $subscriber = new NotificationReadStatusSubscriber($notifications, $users);
        $subscriber->onReadStatusPreChange(new ReadStatusPreChangeEvent(7, 55, ReaderStatus::STATUS_SEEN));
    }
}
