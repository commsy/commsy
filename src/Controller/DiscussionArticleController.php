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
use App\Utils\DiscussionService;
use cs_discussionarticle_item;
use cs_room_item;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;

class DiscussionArticleController extends BaseController
{
    protected DiscussionService $discussionService;

    #[Required]
    public function setDiscussionService(DiscussionService $discussionService): void
    {
        $this->discussionService = $discussionService;
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/discussion_article/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        int $roomId): Response
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_discussionarticle_item[]
     */
    protected function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if (1 == count($itemIds)) {
            return [$this->discussionService->getArticle($itemIds[0])];
        }

        return [];
    }
}
