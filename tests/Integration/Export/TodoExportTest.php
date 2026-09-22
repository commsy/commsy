<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Export;

use App\Entity\Room;
use App\Entity\Todos;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Services\LegacyEnvironment;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A task carries two things an importer cannot read on its own: a status that
 * is a number whose meaning the room decides, and a time whose unit sits in a
 * second column.
 */
final class TodoExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;

    #[WithStory(RoomWithMemberStory::class)]
    public function testATaskStandsInTheDocumentWithItsAuthor(): void
    {
        $itemId = $this->createTodo('Protokoll schreiben');

        $todos = $this->todos();

        $this->assertCount(1, $todos);
        $this->assertSame($itemId, $todos[0]['itemId']);
        $this->assertSame('Protokoll schreiben', $todos[0]['title']);
        $this->assertSame($this->roomUser->getFullname(), $todos[0]['creator']['name']);
        $this->assertArrayHasKey('dueDate', $todos[0]);
        $this->assertArrayHasKey('status', $todos[0]);
    }

    /**
     * The column says `date`, the form says due date, legacy says end date.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheDueDateIsNamedAfterWhatItIs(): void
    {
        $itemId = $this->createTodo('Protokoll schreiben');

        $this->connection->executeStatement(
            "UPDATE todos SET date = '2026-12-24 12:00:00' WHERE item_id = :id",
            ['id' => $itemId]
        );

        $this->assertStringStartsWith('2026-12-24', $this->todos()[0]['dueDate']);
        $this->assertArrayNotHasKey('date', $this->todos()[0]);
    }

    /**
     * The column is called `minutes` but holds whatever unit the second column
     * names, so three days would read as three minutes.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testBookedTimeCarriesItsUnit(): void
    {
        $itemId = $this->createTodo('Protokoll schreiben');

        $this->connection->executeStatement(
            'UPDATE todos SET minutes = 3, time_type = 3 WHERE item_id = :id',
            ['id' => $itemId]
        );

        $this->assertSame(['amount' => 3.0, 'unit' => 'days'], $this->todos()[0]['timeSpent']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testATaskWithoutBookedTimeSaysSo(): void
    {
        $this->createTodo('Protokoll schreiben');

        $this->assertNull($this->todos()[0]['timeSpent']);
    }

    /**
     * Three statuses are CommSy's own, the rest are the room's wording. Without
     * the list on the room a bare number says nothing.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheRoomSaysWhatAStatusNumberMeans(): void
    {
        $itemId = $this->createTodo('Protokoll schreiben');

        $this->giveRoomAnExtraStatus(5, 'Super eilig');
        $this->connection->executeStatement(
            'UPDATE todos SET status = 5 WHERE item_id = :id',
            ['id' => $itemId]
        );

        $document = $this->document();

        $this->assertSame(5, $document['rubrics']['todo'][0]['status']);
        $this->assertSame(
            [
                ['id' => 1, 'name' => 'not_started'],
                ['id' => 2, 'name' => 'in_progress'],
                ['id' => 3, 'name' => 'done'],
                ['id' => 5, 'name' => 'Super eilig'],
            ],
            $document['room']['todoStatuses']
        );
    }

    /**
     * A task can be broken into steps, and a step is an entry in its own right.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testATaskCarriesItsSteps(): void
    {
        $todo = $this->createTodoEntity('Protokoll schreiben');
        StepFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'todo' => $todo,
            'title' => 'Mitschrift sichten',
        ]);

        $steps = $this->todos()[0]['steps'];

        $this->assertCount(1, $steps);
        $this->assertSame('Mitschrift sichten', $steps[0]['title']);
        $this->assertSame([], $steps[0]['files']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedTaskIsLeftOut(): void
    {
        $this->createTodo('Bleibt');
        $deleted = $this->createTodo('Gelöscht');

        $this->connection->executeStatement(
            'UPDATE todos SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], array_column($this->todos(), 'title'));
    }

    /**
     * A step can be a draft while its task is not.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testADraftStepIsLeftOut(): void
    {
        $todo = $this->createTodoEntity('Protokoll schreiben');
        $draft = StepFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'todo' => $todo,
            'title' => 'Entwurf',
        ])->getItemId();

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $draft]);

        $this->assertSame([], $this->todos()[0]['steps']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutTasksCarriesAnEmptyList(): void
    {
        $this->assertSame([], $this->todos());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();

        return self::getContainer()->get(RoomExportBuilder::class)
            ->build($manager->find(Room::class, $this->room->getItemId()))
            ->content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function todos(): array
    {
        return $this->document()['rubrics']['todo'];
    }

    private function createTodo(string $title): int
    {
        return $this->createTodoEntity($title)->getItemId();
    }

    private function createTodoEntity(string $title): Todos
    {
        return TodoFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => $title,
        ]);
    }

    private function giveRoomAnExtraStatus(int $id, string $title): void
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $room = $manager->find(Room::class, $this->room->getItemId());

        $extras = $room->getExtras() ?? [];
        $extras['TODOEXTRASTATUSARRAY'] = [$id => $title];
        $room->setExtras($extras);

        $manager->flush();
    }
}
