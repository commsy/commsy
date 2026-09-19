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

namespace Tests\Integration\Account;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\Portal;
use App\Entity\Room;
use App\Room\RoomManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;

/**
 * Switching the inactivity feature off revokes the pending notifications —
 * but only for the portal whose settings were changed. The query used to run
 * without a portal filter, so one portal moderator reset the accounts and
 * rooms of every other portal as well.
 *
 * Also pins the timestamp rule: it is the deadline base of the state it
 * describes. `active` has no deadline, so it carries null; `idle` has one and
 * gets a fresh one, because the deletion notice it used to carry is revoked.
 */
final class ResetInactivityPerPortalTest extends KernelTestCase
{
    private AccountManager $accountManager;
    private RoomManager $roomManager;
    private EntityManagerInterface $entityManager;

    public function testAccountResetStopsAtThePortalBoundary(): void
    {
        $own = $this->createPortal();
        $other = $this->createPortal();

        $ownNotified = $this->createAccount($own, Account::ACTIVITY_ACTIVE_NOTIFIED);
        $otherNotified = $this->createAccount($other, Account::ACTIVITY_ACTIVE_NOTIFIED);
        $otherIdleNotified = $this->createAccount($other, Account::ACTIVITY_IDLE_NOTIFIED);

        $this->accountManager->resetInactivityToPreviousNonNotificationState($own);
        $this->entityManager->clear();

        self::assertSame(Account::ACTIVITY_ACTIVE, $this->reloadAccount($ownNotified)->getActivityState());
        self::assertSame(
            Account::ACTIVITY_ACTIVE_NOTIFIED,
            $this->reloadAccount($otherNotified)->getActivityState(),
            'A change in one portal must not touch another portal',
        );
        self::assertSame(
            Account::ACTIVITY_IDLE_NOTIFIED,
            $this->reloadAccount($otherIdleNotified)->getActivityState(),
        );
    }

    public function testAccountReturnedToActiveCarriesNoDeadline(): void
    {
        $portal = $this->createPortal();
        $account = $this->createAccount($portal, Account::ACTIVITY_ACTIVE_NOTIFIED);

        $this->accountManager->resetInactivityToPreviousNonNotificationState($portal);
        $this->entityManager->clear();

        $reloaded = $this->reloadAccount($account);
        self::assertSame(Account::ACTIVITY_ACTIVE, $reloaded->getActivityState());
        self::assertNull(
            $reloaded->getActivityStateUpdated(),
            'active has no deadline of its own — the next step is decided by the last login',
        );
    }

    public function testAccountReturnedToIdleGetsAFreshDeadline(): void
    {
        $portal = $this->createPortal();
        $account = $this->createAccount($portal, Account::ACTIVITY_IDLE_NOTIFIED, new DateTime('-300 days'));

        $before = new DateTime();
        $this->accountManager->resetInactivityToPreviousNonNotificationState($portal);
        $this->entityManager->clear();

        $reloaded = $this->reloadAccount($account);
        self::assertSame(Account::ACTIVITY_IDLE, $reloaded->getActivityState());
        self::assertNotNull($reloaded->getActivityStateUpdated());
        self::assertGreaterThanOrEqual(
            $before->modify('-1 second'),
            $reloaded->getActivityStateUpdated(),
            'The revoked deletion notice must not leave its old deadline behind',
        );
    }

    public function testRoomResetStopsAtThePortalBoundary(): void
    {
        $own = $this->createPortal();
        $other = $this->createPortal();

        $ownRoom = $this->createRoom($own, Room::ACTIVITY_ACTIVE_NOTIFIED);
        $otherRoom = $this->createRoom($other, Room::ACTIVITY_ACTIVE_NOTIFIED);

        $this->roomManager->resetInactivityToPreviousNonNotificationState($own);
        $this->entityManager->clear();

        self::assertSame(Room::ACTIVITY_ACTIVE, $this->reloadRoom($ownRoom)->getActivityState());
        self::assertSame(
            Room::ACTIVITY_ACTIVE_NOTIFIED,
            $this->reloadRoom($otherRoom)->getActivityState(),
            'A change in one portal must not touch another portal',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->accountManager = self::getContainer()->get(AccountManager::class);
        $this->roomManager = self::getContainer()->get(RoomManager::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    private function createPortal(): Portal
    {
        return PortalFactory::createOne(['clearInactiveAccountsFeatureEnabled' => true]);
    }

    private function createAccount(Portal $portal, string $state, ?DateTime $stateUpdated = null): Account
    {
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
            'activityState' => $state,
            'activityStateUpdated' => $stateUpdated ?? new DateTime('-40 days'),
        ]);
    }

    private function createRoom(Portal $portal, string $state): Room
    {
        return RoomFactory::new()->project()->create([
            'portal' => $portal,
            'contextId' => $portal->getId(),
            'activityState' => $state,
            'activityStateUpdated' => new DateTime('-40 days'),
        ]);
    }

    private function reloadAccount(Account $account): Account
    {
        return $this->entityManager->getRepository(Account::class)->find($account->getId());
    }

    private function reloadRoom(Room $room): Room
    {
        return $this->entityManager->getRepository(Room::class)->find($room->getItemId());
    }
}
