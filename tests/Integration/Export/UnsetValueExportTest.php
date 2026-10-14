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

use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Some columns allow NULL although every write path fills them. A row that
 * holds NULL anyway says so in the document: the field stands there as null
 * rather than going missing, so a document always has the same keys.
 */
final class UnsetValueExportTest extends KernelTestCase
{
    private Room $room;
    private User $roomUser;
    private Connection $connection;

    /**
     * @return iterable<string, array{Closure(Room, User): int, string, string, list<string|int>}>
     */
    public static function unsetValues(): iterable
    {
        $announcement = static fn (Room $room, User $user): int => AnnouncementFactory::createOne(
            ['room' => $room, 'creator' => $user]
        )->getItemId();
        $comment = static function (Room $room, User $user) use ($announcement): int {
            return AnnotationFactory::createOne(
                ['room' => $room, 'creator' => $user, 'linkedItemId' => $announcement($room, $user)]
            )->getItemId();
        };
        $discussion = static fn (Room $room, User $user): int => DiscussionFactory::createOne(
            ['room' => $room, 'creator' => $user]
        )->getItemId();
        $material = static fn (Room $room, User $user): int => MaterialFactory::createOne(
            ['room' => $room, 'creator' => $user]
        )->getItemId();
        $section = static fn (Room $room, User $user): int => SectionFactory::createOne([
            'room' => $room, 'creator' => $user,
            'material' => MaterialFactory::createOne(['room' => $room, 'creator' => $user]),
        ])->getItemId();
        $todo = static fn (Room $room, User $user): int => TodoFactory::createOne(
            ['room' => $room, 'creator' => $user]
        )->getItemId();
        $step = static fn (Room $room, User $user): int => StepFactory::createOne([
            'room' => $room, 'creator' => $user,
            'todo' => TodoFactory::createOne(['room' => $room, 'creator' => $user]),
        ])->getItemId();

        $entry = static fn (string $rubric, string $field): array => ['rubrics', $rubric, 0, $field];
        $sub = static fn (string $rubric, string $key, string $field): array
            => ['rubrics', $rubric, 0, $key, 0, $field];

        yield 'announcement modified' => [
            $announcement, 'announcement', 'modification_date', $entry('announcement', 'modificationDate'),
        ];
        yield 'comment modified' => [
            $comment, 'annotations', 'modification_date', $sub('announcement', 'annotations', 'modificationDate'),
        ];
        yield 'discussion modified' => [
            $discussion, 'discussions', 'modification_date', $entry('discussion', 'modificationDate'),
        ];
        yield 'discussion description' => [
            $discussion, 'discussions', 'description', $entry('discussion', 'description'),
        ];
        yield 'material modified' => [
            $material, 'materials', 'modification_date', $entry('material', 'modificationDate'),
        ];
        yield 'section created' => [
            $section, 'section', 'creation_date', $sub('material', 'sections', 'creationDate'),
        ];
        yield 'section modified' => [
            $section, 'section', 'modification_date', $sub('material', 'sections', 'modificationDate'),
        ];
        yield 'todo modified' => [
            $todo, 'todos', 'modification_date', $entry('todo', 'modificationDate'),
        ];
        yield 'step created' => [
            $step, 'step', 'creation_date', $sub('todo', 'steps', 'creationDate'),
        ];
        yield 'step modified' => [
            $step, 'step', 'modification_date', $sub('todo', 'steps', 'modificationDate'),
        ];
    }

    /**
     * @param Closure(Room, User): int $create
     * @param list<string|int>         $path
     */
    #[DataProvider('unsetValues')]
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnUnsetValueStandsInTheDocumentAsNull(
        Closure $create,
        string $table,
        string $column,
        array $path,
    ): void {
        $itemId = $create($this->room, $this->roomUser);

        $this->connection->executeStatement(
            'UPDATE '.$table.' SET '.$column.' = NULL WHERE item_id = :id',
            ['id' => $itemId]
        );

        $field = array_pop($path);
        $parent = $this->document();
        foreach ($path as $step) {
            $parent = $parent[$step];
        }

        $this->assertArrayHasKey($field, $parent);
        $this->assertNull($parent[$field]);
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // Read what the database holds, not what the factories left in memory.
        $manager->clear();

        $room = $manager->find(Room::class, $this->room->getItemId());

        return self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;
    }
}
