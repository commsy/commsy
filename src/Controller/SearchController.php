<?php

namespace App\Controller;

use App\Search\FilterConditions\TodoStatusFilterCondition;
use App\Action\Copy\CopyAction;
use App\Filter\SearchFilterType;
use App\Form\Type\SearchItemType;
use App\Form\Type\SearchType;
use App\Model\SearchData;
use App\Search\FilterConditions\CreationDateFilterCondition;
use App\Search\FilterConditions\ModificationDateFilterCondition;
use App\Search\FilterConditions\MultipleCategoryFilterCondition;
use App\Search\FilterConditions\MultipleContextFilterCondition;
use App\Search\FilterConditions\MultipleHashtagFilterCondition;
use App\Search\FilterConditions\ReadStatusFilterCondition;
use App\Search\FilterConditions\RubricFilterCondition;
use App\Search\FilterConditions\SingleContextFilterCondition;
use App\Search\FilterConditions\SingleContextTitleFilterCondition;
use App\Search\FilterConditions\SingleCreatorFilterCondition;
use App\Search\QueryConditions\DescriptionQueryCondition;
use App\Search\QueryConditions\MostFieldsQueryCondition;
use App\Search\QueryConditions\RoomQueryCondition;
use App\Search\QueryConditions\TitleQueryCondition;
use App\Search\SearchManager;
use App\Services\LegacyEnvironment;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function itemSearchResultsAction($roomId,
                                            Request $request,
                                            SearchManager $searchManager,
                                            ReaderService $readerService)
    {
        $query = $request->get('search', '');

        // query conditions
        if (!empty($query)) {
            $mostFieldsQueryCondition = new MostFieldsQueryCondition();
            $mostFieldsQueryCondition->setQuery($query);
            $searchManager->addQueryCondition($mostFieldsQueryCondition);
        }

        // filter conditions
        $singleFilterCondition = new SingleContextFilterCondition();
        $singleFilterCondition->setContextId($roomId);
        $searchManager->addFilterCondition($singleFilterCondition);

        $searchResults = $searchManager->getLinkedItemResults();
        $results = $this->prepareResults($searchResults, $roomId, $readerService, 0, true);

        $response = new JsonResponse();

        $response->setData($results);

        return $response;
    }

    /**
     * @Route("/room/{roomId}/search/instantresults")
     * @param $roomId int The context id
     */
    public function instantResultsAction($roomId,
                                         Request $request,
                                         SearchManager $searchManager,
                                         ReaderService $readerService)
    {
        $query = $request->get('search', '');

        // query conditions
        if (!empty($query)) {
            $mostFieldsQueryCondition = new MostFieldsQueryCondition();
            $mostFieldsQueryCondition->setQuery($query);
            $searchManager->addQueryCondition($mostFieldsQueryCondition);
        }

        // filter conditions
        $singleFilterCondition = new SingleContextFilterCondition();
        $singleFilterCondition->setContextId($roomId);
        $searchManager->addFilterCondition($singleFilterCondition);

        $searchResults = $searchManager->getResults();
        $results = $this->prepareResults($searchResults, $roomId, $readerService, 0, true);

        $response = new JsonResponse();

        $response->setData([
            'results' => $results,
        ]);

        return $response;
    }

    /**
     * Displays search results
     * 
     * @Route("/room/{roomId}/search/results/{sort}")
     * @Template
     */
    public function resultsAction(
        $roomId,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
        ReadStatusFilterCondition $readStatusFilterCondition,
        ReaderService $readerService,
        $sort = 'date_desc'
    ) {
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // extract path extension for sorting
        $path = $request->server->get('HTTP_REFERER');
        if(strpos($path,'_desc')!== false or strpos($path,'_asc') !== false){
            $explodedPath = explode('/', $path);
            if(count($explodedPath) > 0){
                $sort = array_pop($explodedPath);
            }
        }
        $sortingSplit = explode('_',$sort);

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request);

        // if the top form submits a POST request it will call setPhrase() on SearchData
        $topForm = $this->createForm(SearchType::class, $searchData, [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId,
            ])
        ]);
        $topForm->handleRequest($request);

        $sortField = $sortingSplit[0] ?? 'modificationDate';
        $sortOrder = $sortingSplit[1] ?? 'desc';

        /**
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        $this->setupSearchQueryConditions($searchManager, $searchData);
        $this->setupSearchFilterConditions($searchManager, $searchData, $roomId, $multipleContextFilterCondition, $readStatusFilterCondition);

        $searchResults = $searchManager->getResults([$sortField => $sortOrder]);
        $aggregations = $searchResults->getAggregations();

        $countsByRubric = $searchManager->countsByKeyFromAggregation($aggregations['rubrics']);
        $searchData->addRubrics($countsByRubric);

        $countsByCreator = $searchManager->countsByKeyFromAggregation($aggregations['creators']);
        $searchData->addCreators($countsByCreator);

        $countsByHashtag = $searchManager->countsByKeyFromAggregation($aggregations['hashtags']);
        $searchData->addHashtags($countsByHashtag);

        $countsByCategory = $searchManager->countsByKeyFromAggregation($aggregations['tags']);
        $searchData->addCategories($countsByCategory);

        $countsByTodoStatus = $searchManager->countsByKeyFromAggregation($aggregations['todostatuses']);
        $searchData->addTodoStatuses($countsByTodoStatus);

        // if a rubric/creator/hashtag is selected that isn't part of the results anymore, we keep displaying it in the
        // respective search filter form field; this also avoids a form validation error ("this value is not valid")
        $countsByContext = $searchManager->countsByKeyFromAggregation($aggregations['contexts']);
        $searchData->addContexts($countsByContext);

        // if a rubric, creator, hashtag, category or context title is selected that isn't part of the results anymore,
        // we keep displaying it in the respective search filter form field; this also avoids a form validation error
        // ("this value is not valid")
        $selectedRubric = $searchData->getSelectedRubric();
        if (!empty($selectedRubric) && $selectedRubric !== 'all' && !array_key_exists($selectedRubric, $countsByRubric)) {
            $searchData->addRubrics([$selectedRubric => 0]);
        }

        $selectedCreator = $searchData->getSelectedCreator();
        if (!empty($selectedCreator) && $selectedCreator !== 'all' && !array_key_exists($selectedCreator, $countsByCreator)) {
            $searchData->addCreators([$selectedCreator => 0]);
        }

        $selectedHashtags = $searchData->getSelectedHashtags();
        if (!empty($selectedHashtags)) {
            foreach ($selectedHashtags as $hashtag) {
                if (!array_key_exists($hashtag, $countsByHashtag)) {
                    $searchData->addHashtags([$hashtag => 0]);
                }
            }
        }

        $selectedCategories = $searchData->getSelectedCategories();
        if (!empty($selectedCategories)) {
            foreach ($selectedCategories as $category) {
                if (!array_key_exists($category, $countsByCategory)) {
                    $searchData->addCategories([$category => 0]);
                }
            }
        }

        $selectedContext = $searchData->getSelectedContext();
        if (!empty($selectedContext) && $selectedContext !== 'all' && !array_key_exists($selectedContext, $countsByContext)) {
            $searchData->addContexts([$selectedContext => 0]);
        }

        $selectedTodoStatus = $searchData->getSelectedTodoStatus();
        if (!empty($selectedTodoStatus) && $selectedTodoStatus !== 0 && !array_key_exists($selectedTodoStatus, $countsByTodoStatus)) {
            $searchData->addTodoStatuses([$selectedTodoStatus => 0]);
        }

        // if the filter form is submitted by a GET request we use the same data object here to populate the data
        $filterForm = $this->createForm(SearchFilterType::class, $searchData, [
            'contextId' => $roomId,
        ]);
        $filterForm->handleRequest($request);

        $totalHits = $searchResults->getTotalHits();
        $results = $this->prepareResults($searchResults, $roomId, $readerService);

        return [
            'filterForm' => $filterForm->createView(),
            'roomId' => $roomId,
            'totalHits' => $totalHits,
            'results' => $results,
            'searchData' => $searchData,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getEnvironment()->getCurrentUserItem(),
            'sortOrder' => $sortingSplit[1],
            'sortField' => $sortingSplit[0],
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
                                      MultipleContextFilterCondition $multipleContextFilterCondition,
                                      ReadStatusFilterCondition $readStatusFilterCondition,
                                      ReaderService $readerService)
    {
        // NOTE: to have the "load more" functionality work with any applied filters, we also need to add all
        //       SearchFilterType form fields to the "load more" query dictionary in results.html.twig

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request);

        /**
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        $this->setupSearchQueryConditions($searchManager, $searchData);
        $this->setupSearchFilterConditions($searchManager, $searchData, $roomId, $multipleContextFilterCondition, $readStatusFilterCondition);

        $searchResults = $searchManager->getResults();
        $aggregations = $searchResults->getAggregations();

        $countsByRubric = $searchManager->countsByKeyFromAggregation($aggregations['rubrics']);
        $searchData->addRubrics($countsByRubric);

        $countsByCreator = $searchManager->countsByKeyFromAggregation($aggregations['creators']);
        $searchData->addCreators($countsByCreator);

        $countsByHashtag = $searchManager->countsByKeyFromAggregation($aggregations['hashtags']);
        $searchData->addHashtags($countsByHashtag);

        $countsByCategory = $searchManager->countsByKeyFromAggregation($aggregations['tags']);
        $searchData->addCategories($countsByCategory);

        $countsByTodoStatus = $searchManager->countsByKeyFromAggregation($aggregations['todostatuses']);
        $searchData->addTodoStatuses($countsByTodoStatus);

        $countsByContext = $searchManager->countsByKeyFromAggregation($aggregations['contexts']);
        $searchData->addContexts($countsByContext);

        // if the filter form is submitted by a GET request we use the same data object here to populate the data
        $filterForm = $this->createForm(SearchFilterType::class, $searchData, [
            'contextId' => $roomId,
        ]);
        $filterForm->handleRequest($request);

        $results = $this->prepareResults($searchResults, $roomId, $readerService, $start);

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

        $searchParams = $requestParams['search'] ?? $requestParams['search_filter'] ?? null;

        // search phrase parameter
        if (!$searchData->getPhrase()) {
            $searchData->setPhrase($searchParams['phrase'] ?? null);
        }

        // contexts parameter
        $searchData->setSelectedContext($searchParams['selectedContext'] ?? "all");

        // appearing in parameter (based on Lexik\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType)
        $searchData->setAppearsIn($searchParams['appears_in'] ?? []);

        // read status parameter
        $searchData->setSelectedReadStatus($searchParams['selectedReadStatus'] ?? 'all');

        // rubric parameter
        $searchData->setSelectedRubric($searchParams['selectedRubric'] ?? 'all');

        // creator parameter
        $searchData->setSelectedCreator($searchParams['selectedCreator'] ?? "all");

        // hashtags parameter
        $searchData->setSelectedHashtags($searchParams['selectedHashtags'] ?? []);

        // categories parameter
        $searchData->setSelectedCategories($searchParams['selectedCategories'] ?? []);

        // todostatus parameter
        $searchData->setSelectedTodoStatus($searchParams['selectedTodoStatus'] ?? 0);

        // date ranges based on Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType in combination with the UIKit datepicker
        // creation_date_range parameter
        if (!empty($searchParams['creation_date_range'])) {
            $creationDateRange = [];
            if (!empty($searchParams['creation_date_range']['left_date'])) {
                $date = \DateTime::createFromFormat('d.m.Y', $searchParams['creation_date_range']['left_date']);
                if ($date) {
                    $date = $date->setTime(0, 0, 0);
                    $creationDateRange[0] = $date;
                }
            }
            if (!empty($searchParams['creation_date_range']['right_date'])) {
                $date = \DateTime::createFromFormat('d.m.Y', $searchParams['creation_date_range']['right_date']);
                if ($date) {
                    $date = $date->setTime(23, 59, 59);
                    $creationDateRange[1] = $date;
                }
            }
            $searchData->setCreationDateRange($creationDateRange);
        }

        // modification_date_range parameter
        if (!empty($searchParams['modification_date_range'])) {
            $modificationDateRange = [];
            if (!empty($searchParams['modification_date_range']['left_date'])) {
                $date = \DateTime::createFromFormat('d.m.Y', $searchParams['modification_date_range']['left_date']);
                if ($date) {
                    $date = $date->setTime(0, 0, 0);
                    $modificationDateRange[0] = $date;
                }
            }
            if (!empty($searchParams['modification_date_range']['right_date'])) {
                $date = \DateTime::createFromFormat('d.m.Y', $searchParams['modification_date_range']['right_date']);
                if ($date) {
                    $date = $date->setTime(23, 59, 59);
                    $modificationDateRange[1] = $date;
                }
            }
            $searchData->setModificationDateRange($modificationDateRange);
        }

        return $searchData;
    }

    /**
     * Uses the given search manager to add search query conditions for relevant SearchData parameters.
     *
     * @param SearchManager $searchManager
     * @param SearchData $searchData
     */
    public function setupSearchQueryConditions(SearchManager $searchManager,
                                               SearchData $searchData)
    {
        if (!isset($searchManager) || !isset($searchData)) {
            return;
        }

        if (!$searchData->getPhrase()) {
            return;
        }

        // if the search phrase must appear in the title and/or description, we don't need to search any other fields
        if ($searchData->getAppearsInTitle() || $searchData->getAppearsInDescription()) {
            // appears in title parameter
            if ($searchData->getAppearsInTitle()) {
                $titleQueryCondition = new TitleQueryCondition();
                $titleQueryCondition->setTitle($searchData->getPhrase());
                $searchManager->addQueryCondition($titleQueryCondition);
            }

            // appears in description parameter
            if ($searchData->getAppearsInDescription()) {
                $descriptionQueryCondition = new DescriptionQueryCondition();
                $descriptionQueryCondition->setDescription($searchData->getPhrase());
                $searchManager->addQueryCondition($descriptionQueryCondition);
            }
        } else {
            // search phrase parameter
            $mostFieldsQueryCondition = new MostFieldsQueryCondition();
            $mostFieldsQueryCondition->setQuery($searchData->getPhrase());
            $searchManager->addQueryCondition($mostFieldsQueryCondition);
        }
    }

    /**
     * Uses the given search manager to add search filter conditions for relevant SearchData parameters.
     *
     * @param SearchManager $searchManager
     * @param SearchData $searchData
     * @param integer $roomId
     * @param MultipleContextFilterCondition $multipleContextFilterCondition
     * @param ReadStatusFilterCondition $readStatusFilterCondition
     */
    public function setupSearchFilterConditions(SearchManager $searchManager,
                                                SearchData $searchData,
                                                int $roomId,
                                                MultipleContextFilterCondition $multipleContextFilterCondition,
                                                ReadStatusFilterCondition $readStatusFilterCondition)
    {
        if (!isset($searchManager) || !isset($searchData) || empty($roomId) || !isset($multipleContextFilterCondition) || !isset($readStatusFilterCondition)) {
            return;
        }

        // user room IDs / read status parameter
        // NOTE: we always restrict the search to either the context IDs of the current user's rooms, or to item IDs
        // matching the currently selected read status (which is a user-specific property and thus isn't indexed)
        // WARNING: this acts as a PRE-filtering mechanism which can slow things down substantially and ideally
        // wouldn't be necessary
        $selectedReadStatus = $searchData->getSelectedReadStatus();
        if (empty($selectedReadStatus) || $selectedReadStatus === 'all') {
            $searchManager->addFilterCondition($multipleContextFilterCondition);
        } else {
            $readStatusFilterCondition->setReadStatus($selectedReadStatus);
            $searchManager->addFilterCondition($readStatusFilterCondition);
        }

        // context parameter
        if ($searchData->getSelectedContext()) {
            $singleContextTitleFilterCondition = new SingleContextTitleFilterCondition();
            $singleContextTitleFilterCondition->setContextTitle($searchData->getSelectedContext());
            $searchManager->addFilterCondition($singleContextTitleFilterCondition);
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

        // hashtags parameter
        if ($searchData->getSelectedHashtags()) {
            $multipleHashtagFilterCondition = new MultipleHashtagFilterCondition();
            $multipleHashtagFilterCondition->setHashtags($searchData->getSelectedHashtags());
            $searchManager->addFilterCondition($multipleHashtagFilterCondition);
        }

        // categories parameter
        if ($searchData->getSelectedCategories()) {
            $multipleCategoryFilterCondition = new MultipleCategoryFilterCondition();
            $multipleCategoryFilterCondition->setCategories($searchData->getSelectedCategories());
            $searchManager->addFilterCondition($multipleCategoryFilterCondition);
        }

        // creation date range parameter
        if ($searchData->getCreationDateFrom() || $searchData->getCreationDateUntil()) {
            $creationDateFilterCondition = new CreationDateFilterCondition();
            $creationDateFilterCondition->setStartDate($searchData->getCreationDateFrom());
            $creationDateFilterCondition->setEndDate($searchData->getCreationDateUntil());
            $searchManager->addFilterCondition($creationDateFilterCondition);
        }

        // modification date range parameter
        if ($searchData->getModificationDateFrom() || $searchData->getModificationDateUntil()) {
            $modificationDateFilterCondition = new ModificationDateFilterCondition();
            $modificationDateFilterCondition->setStartDate($searchData->getModificationDateFrom());
            $modificationDateFilterCondition->setEndDate($searchData->getModificationDateUntil());
            $searchManager->addFilterCondition($modificationDateFilterCondition);
        }

        // todo status parameter
        if ($searchData->getSelectedRubric() === 'todo' && $searchData->getSelectedTodoStatus()) {
            $todoStatusFilterCondition = new TodoStatusFilterCondition();
            $todoStatusFilterCondition->setTodoStatus($searchData->getSelectedTodoStatus());
            $searchManager->addFilterCondition($todoStatusFilterCondition);
        }
    }

     /**
     * Generates JSON results for the room navigation search-as-you-type form
     *
     * @Route("/room/{roomId}/search/rooms")
     * 
     * @param  int $roomId The current room id
     * @return JsonResponse The JSON result
     */
    public function roomNavigationAction(
        int $roomId,
        Request $request,
        SearchManager $searchManager,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $results = [];

        $query = $request->get('search', '');

        if (!empty($query)) {
            $roomQueryCondition = new RoomQueryCondition();
            $roomQueryCondition->setQuery($query);
            $searchManager->addQueryCondition($roomQueryCondition);
        }

        $roomResults = $searchManager->getRoomResults();

        $rooms = [
            'community' => [],
            'project' => [],
            'grouproom' => [],
            'userroom' => [],
        ];
        foreach ($roomResults as $room) {
            $rooms[$room->getType()][] = $room;
        }

        $rooms = array_merge($rooms['community'], $rooms['project'], $rooms['grouproom'], $rooms['userroom']);

        $lastType = null;
        foreach ($rooms as $room) {
            $url = '#';

            if (!$lastType || $lastType != $room->getType()) {
                if (in_array($room->getType(), ['project', 'community', 'userroom'])) {
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

    private function prepareResults(TransformedPaginatorAdapter $searchResults, $currentRoomId, ReaderService $readerService, $offset = 0, $json = false)
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
                    if ($this->isGranted('ITEM_EDIT', $searchResult->getItemId()) && ($type !== 'user')) {
                        $allowedActions[] = 'delete';
                    }
                }
                // NOTE: the Todos & User entities use a smallint-based status (in case of Todos, it's used for progress status)
                $status = 0;
                if (method_exists($searchResult, 'getStatus')) {
                    $status = $searchResult->getStatus();
                }
                if (method_exists($searchResult, 'getItemId')) {
                    $item = $itemService->getItem($searchResult->getItemId());
                    $readStatus = $readerService->cachedReadStatusForItem($item);
                }
                $results[] = [
                    'allowedActions' => $allowedActions,
                    'entity' => $searchResult,
                    'routeName' => 'app_' . $type . '_detail',
                    'files' => $itemService->getItemFileList($searchResult->getItemId()),
                    'type' => $type,
                    'status' => $status,
                    'readStatus' => $readStatus,
                ];
            }
        }

        return $results;
    }
}
