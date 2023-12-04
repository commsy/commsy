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

namespace App\Controller;

use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteStep;
use App\Action\TodoStatus\TodoStatusAction;
use App\Utils\TodoService;
use cs_room_item;
use cs_step_item;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;

class StepController extends BaseController
{
    private TodoService $todoService;

    public function __construct(private readonly TodoStatusAction $todoStatusAction)
    {
    }

    #[Required]
    public function setTodoService(TodoService $todoService): void
    {
        $this->todoService = $todoService;
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/step/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDelete(
        Request $request,
        DeleteAction $action,
        DeleteStep $deleteStep,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        $action->setDeleteStrategy($deleteStep);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/step/xhr/changestatus/{itemId}', condition: 'request.isXmlHttpRequest()')]
    public function xhrChangeStatus($roomId, $itemId, Request $request, TodoService $todoService): Response
    {
        $items = null;
        $room = $this->getRoom($roomId);
        $roomToDoItems = $todoService->getTodosById($roomId, []);

        foreach ($roomToDoItems as $roomToDoItem) {
            $steps = $roomToDoItem->getStepItemList();
            foreach ($steps as $step) {
                if (0 == strcmp((string) $step->getItemID(), (string) $itemId)) {
                    $items = [$roomToDoItem];
                    $room = $roomToDoItem->getContextItem();
                }
            }
        }

        $payload = $request->request->all('payload');
        if (!isset($payload['status'])) {
            throw new Exception('new status string not provided');
        }
        $newStatus = $payload['status'];

        $action = $this->todoStatusAction;
        $action->setNewStatus($newStatus);

        return $action->execute($room, $items);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_step_item[]
     */
    protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = []): array
    {
        if (1 == count($itemIds)) {
            return [$this->todoService->getStep($itemIds[0])];
        }

        return [];
    }
}
