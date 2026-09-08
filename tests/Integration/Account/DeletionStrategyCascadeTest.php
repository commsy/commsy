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

use App\Account\AccountDeleter;
use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Entity\Account;
use App\Entity\Announcement;
use App\Entity\Room;
use App\Entity\User;
use App\Facade\MembershipManager;
use App\User\UserMembershipDeleter;
use App\Utils\RoomService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that the account's deletion strategy actually reaches the person's
 * entries — on every path that ends a membership, not just on account
 * deletion.
 *
 * The gap this covers: before #5082's follow-up, `eraseUserFootprint()` hung
 * on AccountDeleter alone, so removing a membership inside a room left every
 * entry in place, and a later account deletion could no longer find the room
 * at all (its membership row was already soft-deleted and thus filtered out
 * of every legacy room lookup).
 */
final class DeletionStrategyCascadeTest extends KernelTestCase
{
    private Connection $connection;
    private Room $room;
    private User $roomUser;
    private Account $account;

    // ---------------------------------------------------------------
    // Membership deletion inside a room
    // ---------------------------------------------------------------

    #[WithStory(RoomWithMemberStory::class)]
    public function testModeratorMembershipDeleteDeletesEntriesOnCascade(): void
    {
        $this->setStrategy(cascade: true);
        $announcement = $this->createAnnouncement();

        $this->deleteMembershipAsModerator();

        $this->assertEntryDeleted($announcement);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testModeratorMembershipDeleteKeepsEntriesOnKeep(): void
    {
        $this->setStrategy(cascade: false);
        $announcement = $this->createAnnouncement();

        $this->deleteMembershipAsModerator();

        $this->assertEntryAlive($announcement);
    }

    /**
     * KEEP still has to erase authorship — a surviving entry must not stay
     * attached to the removed person. A NULL creator renders as "-", whereas
     * a reference left pointing at the soft-deleted membership row renders
     * as "deleted person".
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testKeepNullifiesAuthorship(): void
    {
        $this->setStrategy(cascade: false);
        $announcement = $this->createAnnouncement();

        $this->deleteMembershipAsModerator();

        self::assertNull(
            $this->column($announcement, 'creator_id'),
            'a surviving entry must lose its author reference'
        );
        self::assertNull($this->column($announcement, 'modifier_id'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testLeavingTheRoomDeletesEntriesOnCascade(): void
    {
        $this->setStrategy(cascade: true);
        $announcement = $this->createAnnouncement();

        $legacyRoom = self::getContainer()->get(RoomService::class)
            ->getRoomItem($this->room->getItemId());
        self::getContainer()->get(MembershipManager::class)
            ->leaveWorkspace($legacyRoom, $this->account);

        $this->assertEntryDeleted($announcement);
    }

    /**
     * A merge hands the authored rows to the surviving identity before the
     * membership goes away, so the cascade must stay out of it.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testMergeKeepsEntriesEvenOnCascade(): void
    {
        $this->setStrategy(cascade: true);
        $announcement = $this->createAnnouncement();

        self::getContainer()->get(UserMembershipDeleter::class)->softDeleteMembership(
            $this->roomUser->getItemId(),
            1,
            eraseContent: false
        );

        $this->assertEntryAlive($announcement);
        self::assertNotNull(
            $this->column($announcement, 'creator_id'),
            'a merge must leave authorship for mergeAccounts() to rewrite'
        );
    }

    // ---------------------------------------------------------------
    // Account deletion
    // ---------------------------------------------------------------

    #[WithStory(RoomWithMemberStory::class)]
    public function testAccountDeletionDeletesEntriesOnCascade(): void
    {
        $this->setStrategy(cascade: true);
        $announcement = $this->createAnnouncement();

        self::getContainer()->get(AccountDeleter::class)->delete($this->account);

        $this->assertEntryDeleted($announcement);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAccountDeletionKeepsEntriesOnKeep(): void
    {
        $this->setStrategy(cascade: false);
        $announcement = $this->createAnnouncement();

        self::getContainer()->get(AccountDeleter::class)->delete($this->account);

        $this->assertEntryAlive($announcement);
        self::assertNull($this->column($announcement, 'creator_id'));
    }

    /**
     * Documents where the account deletion stops: it finds its rooms through
     * the membership rows, and a row stamped outside
     * {@see UserMembershipDeleter::softDeleteMembership} is filtered out of
     * every legacy room lookup. Such a room is not revisited.
     *
     * Accepted, because the seam is the only way a membership ends in normal
     * operation. The one other stamper is
     * {@see \App\Room\RoomDeletionHelper::softDeleteRoomMemberships} on room
     * deletion, where the room's whole content is soft-deleted anyway.
     *
     * If this ever needs to change, the fix is a pass over the account's
     * already-stamped memberships — not a repair inside the room lookups.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAccountDeletionDoesNotRevisitMembershipsStampedOutsideTheSeam(): void
    {
        $this->setStrategy(cascade: true);
        $announcement = $this->createAnnouncement();

        $this->stampMembershipWithoutErase();

        self::getContainer()->get(AccountDeleter::class)->delete($this->account);

        $this->assertEntryAlive($announcement);
        self::assertNotNull(
            $this->column($announcement, 'creator_id'),
            'nothing touched the entry, so its authorship is still on it'
        );
    }

    /**
     * The counterpart, and a deliberate one-way door: KEEP nullifies the
     * authorship, so nothing ties the entry to the person any more. A later
     * switch to CASCADE cannot reach it — by then the entry is anonymous,
     * which is what the person asked for at the time.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testKeepIsNotRevisitedByALaterCascade(): void
    {
        $this->setStrategy(cascade: false);
        $announcement = $this->createAnnouncement();
        $this->deleteMembershipAsModerator();

        $this->setStrategy(cascade: true);
        self::getContainer()->get(AccountDeleter::class)->delete($this->account);

        $this->assertEntryAlive($announcement);
        self::assertNull($this->column($announcement, 'creator_id'));
    }

    /**
     * Documents the deliberate boundary: the portal-scoped membership row is
     * not cascaded. Its context is the portal, where a cascade would reach
     * portal-wide entries (time pulses, portal-level material) that are
     * shared vocabulary rather than the person's own room content.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testPortalScopedEntriesAreNotCascaded(): void
    {
        $this->setStrategy(cascade: true);

        // A portal has no Room twin, so the factory cannot hang an entry on
        // it — the row goes in directly.
        $itemId = $this->insertPortalAnnouncement($this->portalUserItemId());

        self::getContainer()->get(AccountDeleter::class)->delete($this->account);

        self::assertNull(
            $this->connection->fetchOne(
                'SELECT deletion_date FROM announcement WHERE item_id = :id',
                ['id' => $itemId]
            ),
            'a portal-scoped entry must not be cascaded'
        );
    }

    // ---------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->account = RoomWithMemberStory::get('account');
    }

    /**
     * Both switches at once: the portal has to allow a personal choice, and
     * the personal choice has to say what the test wants.
     */
    private function setStrategy(bool $cascade): void
    {
        $this->account->getPortal()
            ->setAllowUserDefinedDeletionStrategy(true)
            ->setCascadingUserDeletionStrategy(!$cascade); // opposite, so only the personal choice can decide

        self::getContainer()->get(AccountSettingsManager::class)->storeSetting(
            $this->account,
            AccountSetting::USER_DELETION_CASCADING_ITEMS,
            ['enabled' => $cascade]
        );

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($this->account);
        $em->flush();
    }

    private function createAnnouncement(?Room $room = null, ?User $creator = null): Announcement
    {
        return AnnouncementFactory::createOne([
            'room' => $room ?? $this->room,
            'creator' => $creator ?? $this->roomUser,
        ]);
    }

    /** The code path behind the room moderation's bulk "delete membership". */
    private function deleteMembershipAsModerator(): void
    {
        self::getContainer()->get(UserMembershipDeleter::class)
            ->softDeleteMembership($this->roomUser->getItemId(), 1);
    }

    /**
     * Read straight off the column: a portal-scoped row has
     * `context_id == portal_id` and there is no Room twin behind it, so the
     * entity's own `getRoom()` cannot be used to identify it.
     */
    private function portalUserItemId(): int
    {
        $portalId = $this->account->getPortal()->getId();

        $itemId = $this->connection->fetchOne(
            'SELECT item_id FROM user
                WHERE account_id = :accountId AND context_id = :portalId AND deletion_date IS NULL',
            ['accountId' => $this->account->getId(), 'portalId' => $portalId]
        );

        self::assertNotFalse($itemId, 'the story must provide a portal-scoped membership');

        return (int) $itemId;
    }

    /**
     * Stamps the membership row directly, the way
     * RoomDeletionHelper::softDeleteRoomMemberships does — bypassing the
     * seam, so the content stays untouched and still attributed.
     */
    private function stampMembershipWithoutErase(): void
    {
        $this->connection->executeStatement(
            'UPDATE user SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :id',
            ['deleterId' => 1, 'id' => $this->roomUser->getItemId()]
        );
    }

    /** @return int the new item id */
    private function insertPortalAnnouncement(int $portalUserItemId): int
    {
        $portalId = $this->account->getPortal()->getId();

        $this->connection->executeStatement(
            "INSERT INTO items (context_id, type) VALUES (:contextId, 'announcement')",
            ['contextId' => $portalId]
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            "INSERT INTO announcement (item_id, context_id, creator_id, creation_date, title, enddate)
                VALUES (:itemId, :contextId, :creatorId, NOW(), 'portal-wide', NOW())",
            ['itemId' => $itemId, 'contextId' => $portalId, 'creatorId' => $portalUserItemId]
        );

        return $itemId;
    }

    private function column(Announcement $announcement, string $column): ?int
    {
        $value = $this->connection->fetchOne(
            sprintf('SELECT %s FROM announcement WHERE item_id = :id', $column),
            ['id' => $announcement->getItemId()]
        );

        return $value === null || $value === false ? null : (int) $value;
    }

    private function assertEntryDeleted(Announcement $announcement): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM announcement WHERE item_id = :id',
            ['id' => $announcement->getItemId()]
        );

        self::assertNotFalse($row, 'the entry row must still exist — soft-delete, not a hard delete');
        self::assertNotNull($row['deletion_date'], 'the entry must be soft-deleted');
        self::assertNotNull($row['deleter_id']);

        self::assertNotNull(
            $this->connection->fetchOne(
                'SELECT deletion_date FROM items WHERE item_id = :id',
                ['id' => $announcement->getItemId()]
            ),
            'the items twin must follow the rubric row'
        );
    }

    private function assertEntryAlive(Announcement $announcement): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM announcement WHERE item_id = :id',
            ['id' => $announcement->getItemId()]
        );

        self::assertNotFalse($row);
        self::assertNull($row['deletion_date'], 'the entry must survive');
        self::assertNull($row['deleter_id']);
    }
}
