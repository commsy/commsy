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

use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\Mark\InsertAction;
use App\Action\Mark\RemoveAction;
use App\Filter\MarkedFilterType;
use App\Services\MarkedService;
use cs_item;
use cs_room_item;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class MarkedController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class MarkedController extends BaseController
{
    private MarkedService $markedService;

    #[Required]
    public function setMarkedService(MarkedService $markedService): void
    {
        $this->markedService = $markedService;
    }

    #[Route(path: '/room/{roomId}/mark/feed/{start}/{sort}')]
    public function feedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ): Response {
        // extract current filter from parameter bag (embedded controller call)
        // or from query parameters (AJAX)
        $markedFilter = $request->get('markFilter');
        if (!$markedFilter) {
            $markedFilter = $request->query->get('marked_filter');
        }

        $roomItem = $this->loadRoom($roomId);

        if ($roomItem->isPrivateRoom()) {
            $rubrics = [
                'announcement' => 'announcement',
                'material' => 'material',
                'discussion' => 'discussion',
                'date' => 'date',
                'todo' => 'todo',
            ];
        } else {
            $rubrics = $this->roomService->getRubricInformation($roomId);
            $rubrics = array_combine($rubrics, $rubrics);
        }

        if ($markedFilter) {
            // setup filter form
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($markedFilter);

            // apply filter
            $this->markedService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service
        $entries = $this->markedService->getListEntries($max, $start, $sort);

        $stackRubrics = ['date', 'material', 'discussion', 'todo'];

        $allowedActions = [];
        foreach ($entries as $item) {
            if (in_array($item->getItemType(), $rubrics)) {
                $allowedActions[$item->getItemID()][] = 'insert';
            }
            if (in_array($item->getItemType(), $stackRubrics)) {
                $allowedActions[$item->getItemID()][] = 'insertStack';
            }
            $allowedActions[$item->getItemID()][] = 'remove';

            // NOTE: assigning categories (aka tags) and hashtags (aka buzzwords) is room-specific
            //       and can only handle items belonging to the same context
            if ($item->getContextID() === $roomId) {
                $allowedActions[$item->getItemID()][] = 'categorize';
                $allowedActions[$item->getItemID()][] = 'hashtag';
            }
        }

        return $this->render('marked/feed.html.twig', [
            'roomId' => $roomId,
            'entries' => $entries,
            'allowedActions' => $allowedActions,
        ]);
    }

    #[Route(path: '/room/{roomId}/mark')]
    public function listAction(
        Request $request,
        int $roomId
    ): Response {
        $roomItem = $this->loadRoom($roomId);
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions
            $this->markedService->setFilterConditions($filterForm);
        }

        // get number of items
        $itemsCountArray = $this->markedService->getCountArray($roomId);

        return $this->render('marked/list.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'marked',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => null,
            'roomname' => $roomItem->getTitle(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
        ]);
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/mark/xhr/insert', condition: 'request.isXmlHttpRequest()')]
    public function xhrInsertAction(
        Request $request,
        InsertAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/mark/xhr/remove', condition: 'request.isXmlHttpRequest()')]
    public function xhrRemoveAction(
        Request $request,
        RemoveAction $action,
        int $roomId): Response
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/mark/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorizeAction(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/mark/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtagAction(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @param bool  $selectAll
     * @param int[] $itemIds
     *
     * @return cs_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if ($selectAll) {
            if ($request->query->has('marked_filter')) {
                $currentFilter = $request->query->get('marked_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->markedService->setFilterConditions($filterForm);
            }

            return $this->markedService->getListEntries();
        } else {
            return $this->markedService->getMarkedItemsById($itemIds);
        }
    }

    /**
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        if ($room->isPrivateRoom()) {
            $rubrics = [
                'announcement' => 'announcement',
                'material' => 'material',
                'discussion' => 'discussion',
                'date' => 'date',
                'todo' => 'todo',
            ];
        } else {
            $rubrics = $this->roomService->getRubricInformation($room->getItemID());
            $rubrics = array_combine($rubrics, $rubrics);
        }

        return $this->createForm(MarkedFilterType::class, [], [
            'action' => $this->generateUrl('app_marked_list', [
                'roomId' => $room->getItemID(),
            ]),
            'rubrics' => $rubrics,
        ]);
    }

    private function loadRoom(
        int $roomId
    ) {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);

            if (!$roomItem) {
                throw $this->createNotFoundException('The requested room does not exist');
            }
        }

        return $roomItem;
    }
}
