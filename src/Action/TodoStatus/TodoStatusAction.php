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

namespace App\Action\TodoStatus;

use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use App\Utils\TodoService;
use cs_room_item;
use cs_todo_item;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class TodoStatusAction implements ActionInterface
{
    private ?string $newStatus = null;

    public function __construct(private readonly TranslatorInterface $translator, private readonly TodoService $todoService)
    {
    }

    public function setNewStatus(string $newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    /**
     * @param cs_todo_item[] $items
     *
     * @throws Exception
     */
    public function execute(cs_room_item $roomItem, array $items): Response
    {
        if (!$this->newStatus) {
            throw new Exception('no status set for update');
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
            throw new Exception('status not found');
        }

        foreach ($items as $item) {
            $item->setStatus($newStatus);
            $item->save();

            $this->todoService->markTodoReadAndNoticed($item->getItemId(), false, false);
        }

        if (isset($statusMap[$this->newStatus])) {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$this->translator->trans('Set status of %count% entries to '.$this->newStatus, [
                    '%count%' => count($items),
                ]);
        } else {
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$this->translator->trans('Set status of %count% entries to %status%', [
                    '%count%' => count($items),
                    '%status%' => $this->newStatus,
                ]);
        }

        return new JsonDataResponse([
            'message' => $message,
        ]);
    }
}
