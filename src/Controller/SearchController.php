<?php

namespace App\Controller;

use App\Search\FilterConditions\MultipleContextFilterCondition;
use App\Search\FilterConditions\SingleCreatorFilterCondition;
use App\Search\FilterConditions\RubricFilterCondition;
use App\Search\FilterConditions\SingleContextFilterCondition;
use App\Search\SearchManager;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Action\Copy\CopyAction;
use App\Form\Type\SearchItemType;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Form\Type\SearchType;
use App\Model\SearchData;

use App\Filter\SearchFilterType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class SearchController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class SearchController extends BaseController
{
    /**
     * Generates the search form and search field for embedding them into
     * a template.
     * Request data needs to be passed directly, since we can not handle data
     * from the main request here.
     *
     * @Template
     */
    public function searchFormAction($roomId, $requestData)
    {
        $searchData = new SearchData();
        $searchData->setPhrase($requestData['phrase'] ?? null);

        $form = $this->createForm(SearchType::class, $searchData, [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId
            ])
        ]);

//        // manually submit the form
//        if (isset($postData)) {
//            $form->submit($postData);
//        }

        return [
            'form' => $form->createView(),
            'roomId' => $roomId,
        ];
    }

    /**
     * @param $roomId int The id of the containing context
     * @Template
     */
    public function itemSearchFormAction($roomId)
    {
        $form = $this->createForm(SearchItemType::class, [], [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId
            ])
        ]);

        return [
            'form' => $form->createView(),
            'roomId' => $roomId,
        ];
    }

    /**
     * @Route("/room/{roomId}/search/itemresults")
     * @param $roomId
     * @param Request $request
     */
    public function itemSearchResultsAction($roomId, Request $request, SearchManager $searchManager)
    {
        $query = $request->get('search', '');
        $searchManager->setQuery($query);
        $searchManager->setContext($roomId);

        $searchResults = $searchManager->getLinkedItemResults();
        $results = $this->prepareResults($searchResults, $roomId, 0, true);

        $response = new JsonResponse();

        $response->setData($results);

        return $response;
    }

    /**
     * @Route("/room/{roomId}/search/instantresults")
     * @param $roomId int The context id
     */
    public function instantResultsAction($roomId, Request $request, SearchManager $searchManager)
    {
        $query = $request->get('search', '');

        $searchManager->setQuery($query);
        $searchManager->setContext($roomId);

        $searchResults = $searchManager->getResults();
        $results = $this->prepareResults($searchResults, $roomId, 0, true);

        $response = new JsonResponse();

        $response->setData([
            'results' => $results,
        ]);

        return $response;
    }

    /**
     * Displays search results
     * 
     * @Route("/room/{roomId}/search/results")
     * @Template
     */
    public function resultsAction(
        $roomId,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition
    ) {
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request);

        // if the top form submits a POST request it will call setPhrase() on SearchData
        $topForm = $this->createForm(SearchType::class, $searchData, [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId,
            ])
        ]);
        $topForm->handleRequest($request);

        /**
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        // search in all contexts parameter
        if ($searchData->getAllRooms()) {
            $searchManager->addFilterCondition($multipleContextFilterCondition);
        } else {
            $singleFilterCondition = new SingleContextFilterCondition();
            $singleFilterCondition->setContextId($roomId);
            $searchManager->addFilterCondition($singleFilterCondition);
        }

        // rubric parameter
        if ($searchData->getSelectedRubric()) {
            $rubricFilterCondition = new RubricFilterCondition();
            $rubricFilterCondition->setRubric($searchData->getSelectedRubric());
            $searchManager->addFilterCondition($rubricFilterCondition);
        }

        // creator parameter
        if ($searchData->getSelectedCreator()) {
            $singleCreatorFilterCondition = new SingleCreatorFilterCondition();
            $singleCreatorFilterCondition->setCreator($searchData->getSelectedCreator());
            $searchManager->addFilterCondition($singleCreatorFilterCondition);
        }

        $searchManager->setQuery($searchData->getPhrase());
        $searchResults = $searchManager->getResults();
        $aggregations = $searchResults->getAggregations();

        $countsByRubric = $searchManager->countsByKeyFromAggregation($aggregations['rubrics']);
        $searchData->addRubrics($countsByRubric);

        $countsByCreator = $searchManager->countsByKeyFromAggregation($aggregations['creators']);
        $searchData->addCreators($countsByCreator);

        // if the filter form is submitted by a GET request we use the same data object here to populate the data
        $filterForm = $this->createForm(SearchFilterType::class, $searchData, [
            'contextId' => $roomId,
            'parameters' => $request->query,
        ]);
        $filterForm->handleRequest($request);

        $totalHits = $searchResults->getTotalHits();
        $results = $this->prepareResults($searchResults, $roomId);

        return [
            'filterForm' => $filterForm->createView(),
            'roomId' => $roomId,
            'totalHits' => $totalHits,
            'results' => $results,
            'searchData' => $searchData,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getEnvironment()->getCurrentUserItem(),
        ];
    }

    /**
     * Returns more search results
     * 
     * @Route("/room/{roomId}/searchmore/{start}/{sort}")
     * @Template
     */
    public function moreResultsAction($roomId,
                                      $start = 0,
                                      $sort = 'date',
                                      Request $request,
                                      SearchManager $searchManager,
                                      MultipleContextFilterCondition $multipleContextFilterCondition)
    {
        // TODO: to have the "load more" functionality work with any applied filters, we need to add all SearchFilterType form fields to the "load more" query dictionary in results.html.twig!

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request);

        /**
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        // search in all contexts parameter
        if ($searchData->getAllRooms()) {
            $searchManager->addFilterCondition($multipleContextFilterCondition);
        } else {
            $singleFilterCondition = new SingleContextFilterCondition();
            $singleFilterCondition->setContextId($roomId);
            $searchManager->addFilterCondition($singleFilterCondition);
        }

        // rubric parameter
        if ($searchData->getSelectedRubric()) {
            $rubricFilterCondition = new RubricFilterCondition();
            $rubricFilterCondition->setRubric($searchData->getSelectedRubric());
            $searchManager->addFilterCondition($rubricFilterCondition);
        }

        // creator parameter
        if ($searchData->getSelectedCreator()) {
            $singleCreatorFilterCondition = new SingleCreatorFilterCondition();
            $singleCreatorFilterCondition->setCreator($searchData->getSelectedCreator());
            $searchManager->addFilterCondition($singleCreatorFilterCondition);
        }

        $searchManager->setQuery($searchData->getPhrase());
        $searchResults = $searchManager->getResults();
        $aggregations = $searchResults->getAggregations();

        $countsByRubric = $searchManager->countsByKeyFromAggregation($aggregations['rubrics']);
        $searchData->addRubrics($countsByRubric);

        $countsByCreator = $searchManager->countsByKeyFromAggregation($aggregations['creators']);
        $searchData->addCreators($countsByCreator);

        // if the filter form is submitted by a GET request we use the same data object here to populate the data
        $filterForm = $this->createForm(SearchFilterType::class, $searchData, [
            'contextId' => $roomId,
            'parameters' => $request->query,
        ]);
        $filterForm->handleRequest($request);

        $results = $this->prepareResults($searchResults, $roomId, $start);

        return [
            'roomId' => $roomId,
            'results' => $results,
        ];
    }

    /**
     * Populates the given SearchData object with relevant data from the request, and returns it.
     *
     * @param SearchData $searchData
     * @param Request $request
     * @return SearchData
     */
    private function populateSearchData(SearchData $searchData, Request $request): SearchData
    {
        // TODO: should we better move this method to SearchData.php?

        if (!isset($request)) {
            return $searchData;
        }

        $requestParams = $request->query->all();
        if (empty($requestParams)) {
            $requestParams = $request->request->all();
        }
        if (empty($requestParams)) {
            return $searchData;
        }

        // search phrase parameter
        if (!$searchData->getPhrase()) {
            $searchParams = $requestParams['search'] ?? $requestParams['search_filter'] ?? null;
            $searchData->setPhrase($searchParams['phrase'] ?? null);
        }

        // search in all contexts parameter
        $searchData->setAllRooms((!empty($searchParams['all_rooms'])) ? true : false);

        // rubric parameter
        $searchData->setSelectedRubric($searchParams['selectedRubric'] ?? 'all');

        // creator parameter
        $searchData->setSelectedCreator($searchParams['selectedCreator'] ?? "all");

        return $searchData;
    }

     /**
     * Generates JSON results for the room navigation search-as-you-type form
     *
     * @Route("/room/{roomId}/search/rooms")
     * 
     * @param  int $roomId The current room id
     * @return JsonResponse The JSON result
     */
    public function roomNavigationAction($roomId, Request $request, SearchManager $searchManager)
    {
        $results = [];

        $query = $request->get('search', '');

        $router = $this->container->get('router');
        $translator = $this->container->get('translator');

        $searchManager->setQuery($query);

        $roomResults = $searchManager->getRoomResults();

        $rooms = [
            'community' => [],
            'project' => [],
            'grouproom' => [],
        ];
        foreach ($roomResults as $room) {
            $rooms[$room->getType()][] = $room;
        }

        $rooms = array_merge($rooms['community'], $rooms['project'], $rooms['grouproom']);

        $lastType = null;
        foreach ($rooms as $room) {
            $url = '#';

            if (!$lastType || $lastType != $room->getType()) {
                if (in_array($room->getType(), ['project', 'community'])) {
                    $title = $translator->trans(ucfirst($room->getType()) . ' Rooms', [], 'room');
                } else {
                    $title = $translator->trans('Group Rooms', [], 'room');
                }

                $results[] = [
                    'title' => $title,
                    'text' => 'dummy',
                    'url' => $url,
                    'disabled' => true,
                ];
            }

            // construct target url
            $routeName = 'app_room_home';
            if ($router->getRouteCollection()->get($routeName)) {
                $url = $this->generateUrl(
                    $routeName,
                    ['roomId' => $room->getItemId()]
                );
            }

            $results[] = [
                'title' => html_entity_decode($room->getTitle()),
                'text' => $room->getType(),
                'url' => $url,
            ];

            $lastType = $room->getType();
        }

        $response = new JsonResponse();
        $response->setData([
            'results' => $results,
        ]);

        return $response;
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/search/xhr/copy", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrCopyAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(CopyAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/search/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        if ($selectAll) {
            // TODO: This is currently a limitation
            return [];
        } else {
            // TODO: This should be optimized
            $itemService = $this->get('commsy_legacy.item_service');

            $items = [];
            foreach ($itemIds as $itemId) {
                $items[] = $itemService->getTypedItem($itemId);
            }

            return $items;
        }
    }

    private function prepareResults(TransformedPaginatorAdapter $searchResults, $currentRoomId, $offset = 0, $json = false)
    {
        $itemService = $this->get('commsy_legacy.item_service');

        $results = [];
        foreach ($searchResults->getResults($offset, 10)->toArray() as $searchResult) {

            $reflection = new \ReflectionClass($searchResult);
            $type = strtolower(rtrim($reflection->getShortName(), 's'));

            if ($type === 'label') {
                $type = strtolower(rtrim($searchResult->getType(), 's'));
            }

            if ($json) {
                $translator = $this->get('translator');
                $router = $this->container->get('router');

                // construct target url
                $url = '#';

                if ($type == 'room') {
                    $roomId = $currentRoomId;
                    $type = 'project';
                } else {
                    $roomId = $searchResult->getContextId();
                }

                $routeName = 'app_' . $type . '_detail';
                if ($router->getRouteCollection()->get($routeName)) {
                    $url = $this->generateUrl($routeName, [
                        'roomId' => $roomId,
                        'itemId' => $searchResult->getItemId(),
                    ]);
                }

                $title = '';

                if (method_exists($searchResult, 'getTitle')) {
                    $title = $searchResult->getTitle();
                } else if (method_exists($searchResult, 'getName')) {
                    $title = $searchResult->getName();
                } else if (method_exists($searchResult, 'getFirstname')) {
                    $title = $searchResult->getFirstname() . ' ' . $searchResult->getLastname();
                }

                $results[] = [
                    'title' => $title,
                    'text' => $translator->transChoice(ucfirst($type), 0, [], 'rubric'),
                    'url' => $url,
                    'value' => $searchResult->getItemId(),
                ];
            } else {
                $allowedActions = ['copy'];
                if (method_exists($searchResult, 'getItemId')) {
                    if ($this->isGranted('ITEM_EDIT', $searchResult->getItemId())) {
                        $allowedActions[] = 'delete';
                    }
                }
                $results[] = [
                    'allowedActions' => $allowedActions,
                    'entity' => $searchResult,
                    'routeName' => 'app_' . $type . '_detail',
                    'files' => $itemService->getItemFileList($searchResult->getItemId()),
                    'type' => $type,
                ];
            }
        }

        return $results;
    }
}