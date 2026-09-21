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

namespace Tests\Integration\Cron\Tasks;

use App\Cron\Tasks\CronNotifyExpiringAnnouncements;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * An announcement that runs out of validity disappears from its room without a
 * word. These tests pin that its author hears about it a week beforehand — once,
 * and only for announcements that are actually near their end.
 */
class CronNotifyExpiringAnnouncementsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testTheAuthorIsWarnedAboutAnAnnouncementNearingItsEnd(): void
    {
        self::bootKernel();
        [$room, $author, $account] = $this->room();

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $author,
            'enddate' => new \DateTime('+6 days 12 hours'),
        ]);

        $this->runSweep();

        $notifications = $this->repository()->findForAccount($account);
        self::assertCount(1, $notifications);

        $notification = $notifications[0];
        self::assertSame(NotificationType::AnnouncementExpiring, $notification->getType());
        self::assertSame($announcement->getItemId(), $notification->getSourceItemId());
        self::assertSame('announcement', $notification->getSourceItemType(), 'carries the entry, so the bell can link to it');
        self::assertNotNull($notification->getPayload()->dateEnd, 'the expiry date travels with the row');
    }

    public function testASecondSweepDoesNotWarnTwice(): void
    {
        self::bootKernel();
        [$room, $author, $account] = $this->room();
        AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $author,
            'enddate' => new \DateTime('+6 days 12 hours'),
        ]);

        $this->runSweep();
        $this->runSweep();

        self::assertCount(1, $this->repository()->findForAccount($account));
    }

    public function testAnAnnouncementWithPlentyOfTimeLeftIsNotAnnounced(): void
    {
        self::bootKernel();
        [$room, $author, $account] = $this->room();
        AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $author,
            'enddate' => new \DateTime('+30 days'),
        ]);

        $this->runSweep();

        self::assertCount(0, $this->repository()->findForAccount($account));
    }

    private function runSweep(): void
    {
        self::getContainer()->get(CronNotifyExpiringAnnouncements::class)
            ->run(new \DateTimeImmutable('-1 day'));
    }

    /**
     * @return array{0: Room, 1: User, 2: Account}
     */
    private function room(): array
    {
        $account = AccountFactory::createOne();
        $portal = $account->getPortal();
        $room = RoomFactory::createOne([
            'contextId' => $portal->getId(),
            'portal' => $portal,
            'type' => 'project',
        ]);
        $author = RoomUserFactory::createOne(['account' => $account, 'room' => $room]);

        return [$room, $author, $account];
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }
}
