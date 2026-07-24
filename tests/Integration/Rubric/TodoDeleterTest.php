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

use App\Entity\Room;
use App\Entity\Step;
use App\Entity\Todos;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\Rubric\Todo\TodoDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins the TodoDeleter contract: generic `softDeleteItem()` (whole todo incl.
 * steps) and `deleteStep()` (single-step UI flow).
 */
final class TodoDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private TodoDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesTodoAndItemsRows(): void
    {
        $todo = $this->createTodo();

        $this->deleter->softDeleteItem($todo->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('todos', $todo->getItemId());
        $this->assertSoftDeleted('items', $todo->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteCascadesToAllSteps(): void
    {
        $todo = $this->createTodo();
        $a = $this->createStep($todo);
        $b = $this->createStep($todo);
        $c = $this->createStep($todo);

        $this->deleter->softDeleteItem($todo->getItemId(), $this->deleterId);

        foreach ([$a, $b, $c] as $step) {
            $this->assertSoftDeleted('step', $step->getItemId());
            $this->assertSoftDeleted('items', $step->getItemId());
        }
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $todo = $this->createTodo();
        $other = $this->createTodo();
        $step = $this->createStep($todo);

        $this->createLink($todo->getItemId(), $other->getItemId(), 'buzzword_for');
        $this->createLink($step->getItemId(), $other->getItemId(), 'label_for');

        $this->deleter->softDeleteItem($todo->getItemId(), $this->deleterId);

        foreach ([$todo->getItemId(), $step->getItemId()] as $sourceId) {
            $alive = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM links
                    WHERE (from_item_id = :id OR to_item_id = :id)
                      AND deletion_date IS NULL
                      AND deleter_id IS NULL',
                ['id' => $sourceId]
            );
            self::assertSame(0, $alive, sprintf('links referencing item %d must be soft-deleted', $sourceId));
        }
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $todo = $this->createTodo();
        $other = $this->createTodo();
        $step = $this->createStep($todo);

        $linkOnTodo = $this->createLinkItem($todo->getItemId(), $other->getItemId());
        $linkOnStep = $this->createLinkItem($other->getItemId(), $step->getItemId());

        $this->deleter->softDeleteItem($todo->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkOnTodo);
        $this->assertLinkItemSoftDeleted($linkOnStep);
    }

    /**
     * Cascaded steps deliberately get no own event — they are indexed as
     * part of the parent todo's ES document.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $todo = $this->createTodo();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteItem($todo->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'TodoDeleter must dispatch ItemDeletedEvent so ES cleanup etc. can hook in.'
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherTodos(): void
    {
        $target = $this->createTodo();
        $bystander = $this->createTodo();

        $this->deleter->softDeleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('todos', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteStepSoftDeletesRow(): void
    {
        $todo = $this->createTodo();
        $step = $this->createStep($todo);

        $this->deleter->deleteStep($step->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('step', $step->getItemId());
        $this->assertSoftDeleted('items', $step->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteStepDispatchesReindexEventForParentTodo(): void
    {
        $todo = $this->createTodo();
        $step = $this->createStep($todo);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteStep($step->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemReindexEvent::class
        );
        self::assertNotEmpty(
            $dispatched,
            'deleteStep() must dispatch ItemReindexEvent for the parent todo.'
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteStepDoesNotAffectParentOrOtherTodos(): void
    {
        $todo = $this->createTodo();
        $step = $this->createStep($todo);
        $bystander = $this->createTodo();

        $this->deleter->deleteStep($step->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('todos', $todo->getItemId());
        $this->assertNotSoftDeleted('todos', $bystander->getItemId());
    }

    /**
     * Hard-delete covers both `todos` and `step` — closes the legacy gap
     * where CronHardDelete only swept the 'todo' type.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRowsIncludingSteps(): void
    {
        $expired = $this->createTodo();
        $expiredStep = $this->createStep($expired);
        $recent = $this->createTodo();
        $alive = $this->createTodo();

        $this->deleter->softDeleteItem($expired->getItemId(), $this->deleterId);
        $this->deleter->softDeleteItem($recent->getItemId(), $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE todos SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired->getItemId()]
        );
        $this->connection->executeStatement(
            'UPDATE step SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expiredStep->getItemId()]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(2, $affected);
        $this->assertPhysicallyDeleted('todos', $expired->getItemId());
        $this->assertPhysicallyDeleted('step', $expiredStep->getItemId());
        $this->assertSoftDeleted('todos', $recent->getItemId());
        $this->assertNotSoftDeleted('todos', $alive->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(TodoDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createTodo(): Todos
    {
        return TodoFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);
    }

    private function createStep(Todos $todo): Step
    {
        return StepFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'todo' => $todo,
        ]);
    }

    private function createLinkItem(int $firstItemId, int $secondItemId): int
    {
        $linkItem = LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $firstItemId,
            'secondItemId' => $secondItemId,
        ]);

        return $linkItem->getItemId();
    }

    private function createLink(int $fromItemId, int $toItemId, string $linkType): void
    {
        LinkFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'fromItemId' => $fromItemId,
            'toItemId' => $toItemId,
            'linkType' => $linkType,
        ]);
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

    private function assertPhysicallyDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT item_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertFalse($row, sprintf('%s row %d must be physically removed', $table, $itemId));
    }

    private function assertNotSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNull($row['deletion_date'], sprintf('%s row %d must still be alive', $table, $itemId));
        self::assertNull($row['deleter_id'], sprintf('%s row %d must not have deleter_id set', $table, $itemId));
    }

    private function assertLinkItemSoftDeleted(int $linkId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM link_items WHERE item_id = :id',
            ['id' => $linkId]
        );
        self::assertIsArray($row, sprintf('Expected link_items row for id %d', $linkId));
        self::assertNotNull($row['deletion_date'], sprintf('link_items row %d must be soft-deleted', $linkId));
        self::assertSame(
            $this->deleterId,
            (int) $row['deleter_id'],
            sprintf('link_items row %d must record the correct deleter_id', $linkId)
        );

        // cs_link_manager::_create() writes a twin row into `items`
        // (type = 'link_item') to allocate the AUTO_INCREMENT id before
        // inserting into `link_items`. RubricDeletionHelper must soft-delete
        // both sides so the two tables stay in sync.
        $twin = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM items WHERE item_id = :id',
            ['id' => $linkId]
        );
        self::assertIsArray($twin, sprintf('Expected items twin row for link_items id %d', $linkId));
        self::assertNotNull($twin['deletion_date'], sprintf('items twin of link_items %d must be soft-deleted', $linkId));
        self::assertSame(
            $this->deleterId,
            (int) $twin['deleter_id'],
            sprintf('items twin of link_items %d must record the correct deleter_id', $linkId)
        );
    }
}
