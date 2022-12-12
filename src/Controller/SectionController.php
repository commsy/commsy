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
use App\Action\Delete\DeleteSection;
use App\Utils\MaterialService;
use cs_room_item;
use cs_step_item;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SectionController extends BaseController
{
    /**
     * SectionController constructor.
     */
    public function __construct(private MaterialService $materialService, DeleteSection $deleteSection, private DeleteAction $deleteAction)
    {
        $this->deleteAction->setDeleteStrategy($deleteSection);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/section/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeleteAction(
        Request $request,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        // TODO: find a way to load this service via new Symfony Dependency Injection!
        return $this->deleteAction->execute($room, $items);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_step_item[]
     */
    protected function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if (1 == count($itemIds)) {
            return [$this->materialService->getSection($itemIds[0])];
        }

        return [];
    }
}
