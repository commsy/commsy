<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 18:11
 */

namespace App\Action\TodoStatus;


use App\Utils\TodoService;
use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class TodoStatusAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TodoService
     */
    private $todoService;

    /**
     * @var string
     */
    private $newStatus;

    public function __construct(TranslatorInterface $translator, TodoService $todoService)
    {
        $this->translator = $translator;
        $this->todoService = $todoService;
    }

    public function setNewStatus(string $newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    /**
     * @param \cs_room_item $roomItem
     * @param \cs_todo_item[] $items
     * @return Response
     * @throws \Exception
     */
    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        if (!$this->newStatus) {
            throw new \Exception('no status set for update');
        }

        // map status string to int
        $statusMap = [
            'pending' => 1,
            'inprogress' => 2,
            'done' => 3,
        ];

        $newStatus = $statusMap[$this->newStatus] ?? null;
        if (!$newStatus) {
            $customRoomStatus = $roomItem->getExtraToDoStatusArray();
            $newStatus = array_search($this->newStatus, $customRoomStatus);
        }

        if (!$newStatus) {
            throw new \Exception('status not found');
        }

        foreach ($items as $item) {
            $item->setStatus($newStatus);
            $item->save();

            $this->todoService->markTodoReadAndNoticed($item->getItemId(), false, false);
        }

        if (isset($statusMap[$this->newStatus])) {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->transChoice('Set status of %count% entries to ' . $this->newStatus, count($items), [
                '%count%' => count($items),
            ]);
        } else {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->transChoice('Set status of %count% entries to %status%', count($items), [
                '%count%' => count($items),
                '%status%' => $this->newStatus,
            ]);
        }

        return new JsonDataResponse([
            'message' => $message,
        ]);
    }
}