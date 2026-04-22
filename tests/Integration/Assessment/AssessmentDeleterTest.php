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

namespace Tests\Integration\Assessment;

use App\Assessment\AssessmentDeleter;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see AssessmentDeleter}'s four entry points at the DB level.
 * Assessments have no factory (they're per-user ratings, not a rubric), so
 * rows are inserted via DBAL; the rated Announcement comes from the factory.
 */
final class AssessmentDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private AssessmentDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteStampsAssessmentAndItemsTwin(): void
    {
        $ratedItemId = $this->createAnnouncement();
        $itemId = $this->createAssessment($ratedItemId, $this->roomUser->getItemId(), 5);

        $this->deleter->softDelete($itemId, $this->deleterId);

        $this->assertSoftDeleted('assessments', $itemId);
        $this->assertSoftDeleted('items', $itemId);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteDoesNotAffectBystanderAssessments(): void
    {
        $ratedItemId = $this->createAnnouncement();
        $target = $this->createAssessment($ratedItemId, $this->roomUser->getItemId(), 3);
        $bystander = $this->createAssessment($ratedItemId, $this->roomUser->getItemId() + 1, 4);

        $this->deleter->softDelete($target, $this->deleterId);

        $this->assertSoftDeleted('assessments', $target);
        $this->assertNotSoftDeleted('assessments', $bystander);
    }

    /**
     * CASCADE_ITEMS mode in {@see \App\Rubric\UserContentDeleter}.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteAssessmentsByUserSweepsAllAliveAssessments(): void
    {
        $ratedItemId = $this->createAnnouncement();
        $userId = $this->roomUser->getItemId();
        $otherUserId = $userId + 1;

        $mineOne = $this->createAssessment($ratedItemId, $userId, 3);
        $mineTwo = $this->createAssessment($ratedItemId, $userId, 5);
        $other = $this->createAssessment($ratedItemId, $otherUserId, 4);

        $this->deleter->softDeleteAssessmentsByUser($userId, $this->room->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('assessments', $mineOne);
        $this->assertSoftDeleted('assessments', $mineTwo);
        $this->assertNotSoftDeleted('assessments', $other);
    }

    /**
     * KEEP_ITEMS mode: rating values survive (they still feed the item's
     * average), authorship is erased on every row — alive or soft-deleted.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testNullifyReferencesInContextClearsCreatorOnAllRows(): void
    {
        $ratedItemId = $this->createAnnouncement();
        $userId = $this->roomUser->getItemId();

        $alive = $this->createAssessment($ratedItemId, $userId, 3);
        $softDeleted = $this->createAssessment($ratedItemId, $userId, 2);
        $otherUser = $this->createAssessment($ratedItemId, $userId + 1, 4);

        $this->connection->executeStatement(
            'UPDATE assessments SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $this->deleterId, 'itemId' => $softDeleted]
        );

        $this->deleter->nullifyReferencesInContext($userId, $this->room->getItemId());

        self::assertNull($this->fetchCreatorId($alive), 'alive assessment must lose creator');
        self::assertNull($this->fetchCreatorId($softDeleted), 'soft-deleted assessment must also lose creator');
        self::assertSame($userId + 1, $this->fetchCreatorId($otherUser), 'other users\' assessments must stay intact');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRows(): void
    {
        $ratedItemId = $this->createAnnouncement();
        $userId = $this->roomUser->getItemId();

        $expired = $this->createAssessment($ratedItemId, $userId, 1);
        $recent = $this->createAssessment($ratedItemId, $userId, 2);
        $alive = $this->createAssessment($ratedItemId, $userId, 5);

        $this->deleter->softDelete($expired, $this->deleterId);
        $this->deleter->softDelete($recent, $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE assessments SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(1, $affected);
        self::assertFalse($this->assessmentExists($expired), 'expired assessment row must be physically gone');
        self::assertTrue($this->assessmentExists($recent), 'recent soft-delete must still be present');
        self::assertTrue($this->assessmentExists($alive), 'alive assessment must stay intact');
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(AssessmentDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createAnnouncement(): int
    {
        $announcement = AnnouncementFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);

        return $announcement->getItemId();
    }

    private function createAssessment(int $ratedItemId, int $creatorId, int $value): int
    {
        $this->connection->executeStatement(
            'INSERT INTO items (context_id, type, modification_date) VALUES (:ctx, :type, NOW())',
            ['ctx' => $this->room->getItemId(), 'type' => 'assessment']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO assessments (item_id, context_id, creator_id, creation_date, item_link_id, assessment)
                VALUES (:itemId, :ctx, :creatorId, NOW(), :ratedItemId, :value)',
            [
                'itemId' => $itemId,
                'ctx' => $this->room->getItemId(),
                'creatorId' => $creatorId,
                'ratedItemId' => $ratedItemId,
                'value' => $value,
            ]
        );

        return $itemId;
    }

    private function assertSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNotNull($row['deletion_date'], sprintf('%s row %d must have deletion_date set', $table, $itemId));
        self::assertSame(
            $this->deleterId,
            (int) $row['deleter_id'],
            sprintf('%s row %d must record the correct deleter_id', $table, $itemId)
        );
    }

    private function assertNotSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNull($row['deletion_date'], sprintf('%s row %d must still be alive', $table, $itemId));
    }

    private function fetchCreatorId(int $itemId): ?int
    {
        $value = $this->connection->fetchOne(
            'SELECT creator_id FROM assessments WHERE item_id = :id',
            ['id' => $itemId]
        );

        return $value === false || $value === null ? null : (int) $value;
    }

    private function assessmentExists(int $itemId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM assessments WHERE item_id = :id',
            ['id' => $itemId]
        );
    }
}
