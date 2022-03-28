<?php

namespace App\Controller;

use App\Action\Copy\InsertAction;
use App\Action\Copy\RemoveAction;
use App\Services\CopyService;
use App\Services\LegacyEnvironment;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Filter\CopyFilterType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class CopyController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class CopyController extends BaseController
{
    /**
     * @var CopyService
     */
    private CopyService $copyService;

    /**
     * @required
     * @param CopyService $copyService
     */
    public function setCopyService(CopyService $copyService): void
    {
        $this->copyService = $copyService;
    }

    /**
     * @Route("/room/{roomId}/copy/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query parameters (AJAX)
        $copyFilter = $request->get('copyFilter');
        if (!$copyFilter) {
            $copyFilter = $request->query->get('copy_filter');
        }

        $roomItem = $this->loadRoom($roomId);

        if ($roomItem->isPrivateRoom()) {
            $rubrics = [
                "announcement" => "announcement",
                "material" => "material",
                "discussion" => "discussion",
                "date" => "date",
                "todo" => "todo",
            ];
        } else {
            $rubrics = $this->roomService->getRubricInformation($roomId);
            $rubrics = array_combine($rubrics, $rubrics);
        }

        if ($copyFilter) {
            // setup filter form
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($copyFilter);

            // apply filter
            $this->copyService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service 
        $entries = $this->copyService->getListEntries($max, $start, $sort);

        $stackRubrics = ['date', 'material', 'discussion', 'todo'];

        $allowedActions = array();
        foreach ($entries as $item) {
            if (in_array($item->getItemType(), $rubrics)) {
                $allowedActions[$item->getItemID()][] = 'insert';
            }
            if (in_array($item->getItemType(), $stackRubrics)) {
                $allowedActions[$item->getItemID()][] = 'insertStack';
            }
            $allowedActions[$item->getItemID()][] = 'remove';
        }

        return [
            'roomId' => $roomId,
            'entries' => $entries,
            'allowedActions' => $allowedActions,
        ];
    }

    /**
     * @Route("/room/{roomId}/copy")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        int $roomId
    ) {
        $roomItem = $this->loadRoom($roomId);
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions
            $this->copyService->setFilterConditions($filterForm);
        }

        // get number of items
        $itemsCountArray = $this->copyService->getCountArray($roomId);

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'copies',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => null,
            'roomname' => $roomItem->getTitle(),
        ];
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/copy/xhr/insert", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrInsertAction(
        Request $request,
        InsertAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/copy/xhr/remove", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return mixed
     * @throws Exception
     */
    public function xhrRemoveAction(
        Request $request,
        RemoveAction $action,
        int $roomId)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {

        if ($selectAll) {
            if ($request->query->has('copy_filter')) {
                $currentFilter = $request->query->get('copy_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->copyService->setFilterConditions($filterForm);
            }

            return $this->copyService->getListEntries();
        } else {
            return $this->copyService->getCopiesById($itemIds);
        }
    }

    /**
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        if ($room->isPrivateRoom()) {
            $rubrics = [
                "announcement" => "announcement",
                "material" => "material",
                "discussion" => "discussion",
                "date" => "date",
                "todo" => "todo",
            ];
        } else {
            $rubrics = $this->roomService->getRubricInformation($room->getItemID());
            $rubrics = array_combine($rubrics, $rubrics);
        }

        return $this->createForm(CopyFilterType::class, [], [
            'action' => $this->generateUrl('app_copy_list', [
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
