<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 15:24
 */

namespace App\Action\MarkRead;


use App\Utils\TodoService;

class MarkReadTodo implements MarkReadInterface
{
    /**
     * @var TodoService
     */
    private $todoService;

    public function __construct(TodoService $todoService)
    {
        $this->todoService = $todoService;
    }

    /**
     * @param \cs_item $item
     */
    public function markRead(\cs_item $item): void
    {
        $this->todoService->markTodoReadAndNoticed($item->getItemId());
    }
}