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

namespace Tests\Integration\Export;

use App\Entity\Announcement;
use App\Entity\Dates;
use App\Entity\Discussions;
use App\Entity\Labels;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Todos;
use App\Entity\User;
use App\Export\RubricExportSource;
use App\Export\Source\AnnouncementExportSource;
use App\Export\Source\DateExportSource;
use App\Export\Source\DiscussionExportSource;
use App\Export\Source\LabelExportSource;
use App\Export\Source\MaterialExportSource;
use App\Export\Source\TodoExportSource;
use App\Rubric\RubricType;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\DatesFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * What every source answers on the interface's behalf: which entity it serves,
 * and one entry by id on the same terms as the list — none that was deleted,
 * none that is a draft.
 */
final class RubricExportSourceTest extends KernelTestCase
{
    private Room $room;
    private User $roomUser;
    private Connection $connection;

    /**
     * @return iterable<string, array{class-string<RubricExportSource>, RubricType, class-string, string, Closure(Room, User): int}>
     */
    public static function sources(): iterable
    {
        yield 'announcement' => [
            AnnouncementExportSource::class, RubricType::Announcement, Announcement::class, 'announcement',
            static fn (Room $room, User $user): int => AnnouncementFactory::createOne(
                ['room' => $room, 'creator' => $user]
            )->getItemId(),
        ];
        yield 'date' => [
            DateExportSource::class, RubricType::Date, Dates::class, 'dates',
            static fn (Room $room, User $user): int => DatesFactory::createOne(
                ['room' => $room, 'creator' => $user]
            )->getItemId(),
        ];
        yield 'discussion' => [
            DiscussionExportSource::class, RubricType::Discussion, Discussions::class, 'discussions',
            static fn (Room $room, User $user): int => DiscussionFactory::createOne(
                ['room' => $room, 'creator' => $user]
            )->getItemId(),
        ];
        yield 'label' => [
            LabelExportSource::class, RubricType::Label, Labels::class, 'labels',
            static fn (Room $room, User $user): int => LabelFactory::createOne(
                ['room' => $room, 'creator' => $user, 'type' => 'topic', 'name' => 'Thema']
            )->getItemId(),
        ];
        yield 'material' => [
            MaterialExportSource::class, RubricType::Material, Materials::class, 'materials',
            static fn (Room $room, User $user): int => MaterialFactory::createOne(
                ['room' => $room, 'creator' => $user]
            )->getItemId(),
        ];
        yield 'todo' => [
            TodoExportSource::class, RubricType::Todo, Todos::class, 'todos',
            static fn (Room $room, User $user): int => TodoFactory::createOne(
                ['room' => $room, 'creator' => $user]
            )->getItemId(),
        ];
    }

    /**
     * @param class-string<RubricExportSource> $sourceClass
     * @param class-string                     $entryClass
     * @param Closure(Room, User): int         $create
     */
    #[DataProvider('sources')]
    #[WithStory(RoomWithMemberStory::class)]
    public function testASourceSaysWhatItServesAndFindsAnEntryTheListHolds(
        string $sourceClass,
        RubricType $rubric,
        string $entryClass,
        string $table,
        Closure $create,
    ): void {
        $itemId = $create($this->room, $this->roomUser);
        $source = $this->source($sourceClass);

        $found = $source->find($itemId);

        $this->assertSame($rubric, $source->rubric());
        $this->assertSame($entryClass, $source->entryClass());
        $this->assertInstanceOf($entryClass, $found);
        $this->assertSame($itemId, $found->getItemId());
        $this->assertSame(
            [$itemId],
            array_map(
                static fn ($entry): int => $entry->getItemId(),
                [...$source->entriesFor($this->managedRoom())]
            )
        );
    }

    #[DataProvider('sources')]
    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedEntryIsNotFound(
        string $sourceClass,
        RubricType $rubric,
        string $entryClass,
        string $table,
        Closure $create,
    ): void {
        $itemId = $create($this->room, $this->roomUser);

        foreach ([$table, 'items'] as $deleteFrom) {
            $this->connection->executeStatement(
                'UPDATE '.$deleteFrom.' SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
                ['deleter' => $this->roomUser->getItemId(), 'id' => $itemId]
            );
        }

        $this->assertNull($this->source($sourceClass)->find($itemId));
    }

    #[DataProvider('sources')]
    #[WithStory(RoomWithMemberStory::class)]
    public function testADraftIsNotFound(
        string $sourceClass,
        RubricType $rubric,
        string $entryClass,
        string $table,
        Closure $create,
    ): void {
        $itemId = $create($this->room, $this->roomUser);

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $itemId]);

        $this->assertNull($this->source($sourceClass)->find($itemId));
    }

    #[DataProvider('sources')]
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnUnknownEntryIsNotFound(string $sourceClass): void
    {
        $this->assertNull($this->source($sourceClass)->find(999_999_999));
    }

    /**
     * Hashtags are labels too, but they stand on the room as its vocabulary and
     * are not an entry of the rubric.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAHashtagIsNotALabelEntry(): void
    {
        $hashtag = LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => 'buzzword',
            'name' => 'Prüfung',
        ])->getItemId();

        $this->assertNull($this->source(LabelExportSource::class)->find($hashtag));
    }

    /**
     * Only the version the room shows is an entry.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAMaterialIsFoundAtItsNewestVersion(): void
    {
        $itemId = MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => 'Skript',
        ])->getItemId();

        $this->connection->executeStatement(
            'INSERT INTO materials (item_id, version_id, context_id, creator_id, creation_date, title, workflow_status)
             SELECT item_id, version_id + 1, context_id, creator_id, creation_date, :title, workflow_status
             FROM materials WHERE item_id = :id',
            ['title' => 'Skript, überarbeitet', 'id' => $itemId]
        );

        $found = $this->source(MaterialExportSource::class)->find($itemId);

        $this->assertInstanceOf(Materials::class, $found);
        $this->assertSame('Skript, überarbeitet', $found->getTitle());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    private function source(string $class): RubricExportSource
    {
        return self::getContainer()->get($class);
    }

    private function managedRoom(): Room
    {
        return self::getContainer()->get(EntityManagerInterface::class)->find(Room::class, $this->room->getItemId());
    }
}
