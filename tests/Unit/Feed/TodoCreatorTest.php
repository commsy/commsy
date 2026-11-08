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

namespace Tests\Unit\Feed;

use App\Feed\Creators\TodoCreator;
use cs_todo_item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * The feed names a task with its due date, or without one when it has none.
 */
final class TodoCreatorTest extends TestCase
{
    public function testATaskWithDueDateIsTitledWithIt(): void
    {
        self::assertSame('Task: Bericht (due date 04.03.2030)', $this->creator()->getTitle($this->task('2030-03-04 10:30:00')));
    }

    public function testATaskWithoutDueDateIsTitledWithoutOne(): void
    {
        self::assertSame('Task: Bericht', $this->creator()->getTitle($this->task(null)));
    }

    private function creator(): TodoCreator
    {
        $creator = new TodoCreator();
        $creator->setTranslator(new IdentityTranslator());

        return $creator;
    }

    private function task(?string $dueDate): cs_todo_item
    {
        $task = $this->createStub(cs_todo_item::class);
        $task->method('getTitle')->willReturn('Bericht');
        $task->method('getDate')->willReturn($dueDate);

        return $task;
    }
}
