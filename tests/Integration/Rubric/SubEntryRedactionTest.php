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

namespace Tests\Integration\Rubric;

use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Event\ItemReindexEvent;
use App\Rubric\Discussion\DiscussionArticleRedactor;
use App\Rubric\Material\SectionRedactor;
use App\Rubric\RedactionText;
use App\Rubric\Todo\StepRedactor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that redacting a sub-entry writes the replacement into the database
 * instead of leaving the original content behind a display-time marker, and
 * that the attachments follow the content.
 */
final class SubEntryRedactionTest extends KernelTestCase
{
    private Connection $connection;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testSectionTitleAndDescriptionAreOverwritten(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $section = SectionFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'material' => $material,
        ]);

        self::getContainer()->get(SectionRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        $row = $this->connection->fetchAssociative(
            'SELECT title, description, deletion_date FROM section WHERE item_id = :id',
            ['id' => $section->getItemId()]
        );

        self::assertSame($this->expectedTitle(), $row['title'], 'the title must be replaced, not kept');
        self::assertSame($this->expectedDescription(), $row['description']);
        self::assertNull($row['deletion_date'], 'the row itself survives so the material keeps its structure');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSectionAttachmentIsDetachedAndFileEndsItsLife(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $section = SectionFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'material' => $material,
        ]);
        $fileId = $this->attachFile($section->getItemId(), versionId: 1);

        self::getContainer()->get(SectionRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        $link = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM item_link_file WHERE file_id = :id',
            ['id' => $fileId]
        );
        self::assertNotNull(
            $link['deletion_date'],
            'the attachment link must be stamped, otherwise the file stays downloadable'
        );

        $file = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM files WHERE files_id = :id',
            ['id' => $fileId]
        );
        self::assertNotNull(
            $file['deletion_date'],
            'the file lost its last live link and must enter the retention window'
        );

        // Both stamps carry the id handed in, not whoever the legacy
        // environment happens to consider the current user — a deletion
        // running in a background worker has none.
        self::assertSame($this->roomUser->getItemId(), (int) $link['deleter_id']);
        self::assertSame($this->roomUser->getItemId(), (int) $file['deleter_id']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testStepContentAndTimeEntryAreOverwritten(): void
    {
        $todo = TodoFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $step = StepFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'todo' => $todo,
        ]);
        $this->connection->executeStatement(
            'UPDATE step SET minutes = 42 WHERE item_id = :id',
            ['id' => $step->getItemId()]
        );

        self::getContainer()->get(StepRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        $row = $this->connection->fetchAssociative(
            'SELECT title, description, minutes FROM step WHERE item_id = :id',
            ['id' => $step->getItemId()]
        );

        self::assertSame($this->expectedTitle(), $row['title']);
        self::assertSame($this->expectedDescription(), $row['description']);
        self::assertSame(0, (int) $row['minutes'], 'the time entry is the person\'s own data');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDiscussionArticleDescriptionIsOverwrittenAndThreadPositionKept(): void
    {
        $discussion = DiscussionFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $article = DiscussionArticleFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'discussion' => $discussion,
        ]);
        $positionBefore = $this->connection->fetchOne(
            'SELECT position FROM discussionarticles WHERE item_id = :id',
            ['id' => $article->getItemId()]
        );

        self::getContainer()->get(DiscussionArticleRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        $row = $this->connection->fetchAssociative(
            'SELECT description, position, deletion_date FROM discussionarticles WHERE item_id = :id',
            ['id' => $article->getItemId()]
        );

        self::assertSame($this->expectedDescription(), $row['description']);
        self::assertSame($positionBefore, $row['position'], 'the thread tree must survive');
        self::assertNull($row['deletion_date']);
    }

    /**
     * A sub-entry by somebody else must not be touched, even in the same
     * material — the redactors work by creator, not by parent.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testSubEntriesOfOtherPeopleAreUntouched(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $other = $this->otherRoomUser();
        $foreignSection = SectionFactory::createOne([
            'room' => $this->room, 'creator' => $other, 'material' => $material,
        ]);
        $titleBefore = $this->connection->fetchOne(
            'SELECT title FROM section WHERE item_id = :id',
            ['id' => $foreignSection->getItemId()]
        );

        self::getContainer()->get(SectionRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        self::assertSame($titleBefore, $this->connection->fetchOne(
            'SELECT title FROM section WHERE item_id = :id',
            ['id' => $foreignSection->getItemId()]
        ));
    }

    /**
     * The parent's search document embeds the sub-entry text, so the
     * replacement is worthless until the parent is reindexed.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testParentIsReindexedSoTheSearchLosesTheOldText(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        SectionFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'material' => $material,
        ]);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        self::getContainer()->get(SectionRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        $reindexed = array_filter(
            $dispatcher->getCalledListeners(),
            static fn (array $call): bool => $call['event'] === ItemReindexEvent::class
        );
        self::assertNotEmpty($reindexed, 'the material must be reindexed after its section was replaced');
    }

    /**
     * A file still carried by a surviving entry must keep its life — the
     * sweep is per file, not per link.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testFileSharedWithASurvivingEntryStaysAlive(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $section = SectionFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'material' => $material,
        ]);
        $other = $this->otherRoomUser();
        $survivor = SectionFactory::createOne([
            'room' => $this->room, 'creator' => $other, 'material' => $material,
        ]);

        $fileId = $this->attachFile($section->getItemId(), versionId: 1);
        $this->connection->executeStatement(
            'INSERT INTO item_link_file (item_iid, item_vid, file_id) VALUES (:itemId, 1, :fileId)',
            ['itemId' => $survivor->getItemId(), 'fileId' => $fileId]
        );

        self::getContainer()->get(SectionRedactor::class)
            ->redactContentOfUser($this->roomUser->getItemId(), $this->room->getItemId());

        self::assertNull(
            $this->connection->fetchOne('SELECT deletion_date FROM files WHERE files_id = :id', ['id' => $fileId]),
            'another entry still carries the file, so it must not enter the retention window'
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
    }

    private function expectedTitle(): string
    {
        return self::getContainer()->get(RedactionText::class)->title($this->room->getItemId());
    }

    private function expectedDescription(): string
    {
        return self::getContainer()->get(RedactionText::class)->description($this->room->getItemId());
    }

    private function otherRoomUser(): User
    {
        $account = \Tests\Factory\AccountFactory::createOne([
            'portal' => $this->room->getPortal(),
            'authSource' => $this->room->getPortal()->getAuthSources()->first(),
        ]);

        return \Tests\Factory\RoomUserFactory::createOne([
            'room' => $this->room,
            'account' => $account,
        ]);
    }

    private function attachFile(int $itemId, int $versionId): int
    {
        $this->connection->executeStatement(
            "INSERT INTO files (portal_id, context_id, creator_id, creation_date, filename, filepath, size)
                VALUES (:portalId, :contextId, :creatorId, NOW(), 'attachment.pdf', '/tmp/attachment.pdf', 100)",
            [
                'portalId' => $this->room->getPortal()->getId(),
                'contextId' => $this->room->getItemId(),
                'creatorId' => $this->roomUser->getItemId(),
            ]
        );
        $fileId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO item_link_file (item_iid, item_vid, file_id) VALUES (:itemId, :versionId, :fileId)',
            ['itemId' => $itemId, 'versionId' => $versionId, 'fileId' => $fileId]
        );

        return $fileId;
    }
}
