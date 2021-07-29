<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Delete\DeleteAction;
use App\Entity\SavedSearch;
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
use App\Search\FilterConditions\TodoStatusFilterCondition;
use App\Search\QueryConditions\DescriptionQueryCondition;
use App\Search\QueryConditions\MostFieldsQueryCondition;
use App\Search\QueryConditions\RoomQueryCondition;
use App\Search\QueryConditions\TitleQueryCondition;
use App\Search\SearchManager;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;
use cs_item;
use cs_room_item;
use Exception;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * SearchController constructor.
     * @param RoomService $roomService
     * @param UrlGeneratorInterface $router
     */
    public function __construct(RoomService $roomService, UrlGeneratorInterface $router)
    {
        parent::__construct($roomService);
        $this->router = $router;
    }

    /**
     * Generates the search form and search field for embedding them into
     * a template.
     * Request data needs to be passed directly, since we can not handle data
     * from the main request here.
     *
     * @Template
     * @param int $roomId
     * @param $requestData
     * @return array
     */
    public function searchFormAction(
        int $roomId,
        $requestData,
        RoomService $roomService
    ) {
        $searchData = new SearchData();
        $searchData->setPhrase($requestData['phrase'] ?? null);

        $originalRoomId = $roomId;
        $originalRoomItem = $roomService->getRoomItem($roomId);

        // by default, we perform a global search across all of the user's rooms, so we redirect to the dashboard
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $privateRoomItem = $currentUser->getOwnRoom();
        $privateRoomID = ($privateRoomItem) ? $privateRoomItem->getItemID() : null;
        if ($privateRoomID) {
            $roomId = $privateRoomID;
        }

        $form = $this->createForm(SearchType::class, $searchData, [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId
            ])
        ]);

        return [
            'form' => $form->createView(),
            'roomId' => $roomId,
            'originalRoomId' => $originalRoomId,
            'originalRoomTitle' => $originalRoomItem ? $originalRoomItem->getTitle() : '',
        ];
    }

    /**
     * @param $roomId int The id of the containing context
     * @Template
     */
    public function itemSearchFormAction(
        int $roomId
    ) {
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
     * @param Request $request
     * @param SearchManager $searchManager
     * @param int $roomId
     * @return JsonResponse
     */
    public function itemSearchResultsAction(
        Request $request,
        SearchManager $searchManager,
        ReaderService $readerService,
        int $roomId
    ) {
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
        $results = $this->prepareResults($searchResults, $readerService,  $roomId, 0, true);

        $response = new JsonResponse();

        $response->setData($results);

        return $response;
    }

    /**
     * @Route("/room/{roomId}/search/instantresults")
     * @param Request $request
     * @param SearchManager $searchManager
     * @param $roomId int The context id
     * @return JsonResponse
     */
    public function instantResultsAction(
        Request $request,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
                                         ReaderService $readerService,
        int $roomId
    ) {
        $query = $request->get('search', '');

        // query conditions
        if (!empty($query)) {
            $mostFieldsQueryCondition = new MostFieldsQueryCondition();
            $mostFieldsQueryCondition->setQuery($query);
            $searchManager->addQueryCondition($mostFieldsQueryCondition);
        }

        // filter conditions
        // NOTE: instant results will always perform a global search, i.e. show best matches from all of the user's rooms
        $searchManager->addFilterCondition($multipleContextFilterCondition);

        $searchResults = $searchManager->getResults();
        $results = $this->prepareResults($searchResults, $readerService, $roomId, 0, true, $query);

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
     * @param Request $request
     * @param RoomService $roomService
     * @param SearchManager $searchManager
     * @param MultipleContextFilterCondition $multipleContextFilterCondition
     * @param int $roomId
     * @return array
     */
    public function resultsAction(
        Request $request,
        RoomService $roomService,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
        ReadStatusFilterCondition $readStatusFilterCondition,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ReaderService $readerService,
        int $roomId
    )
    {
        $roomItem = $roomService->getRoomItem($roomId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request, $currentUser);

        // the `originalContext` query parameter exists if the user clicked the 'Search in this room' entry in the
        // instant results dropdown; the param contains the roomId of the original room that was active before the
        // search caused a redirect to the dashboard
        $originalRoomId = $request->get('originalContext');
        $originalRoomItem = ($originalRoomId) ? $roomService->getRoomItem($originalRoomId) : null;
        if ($originalRoomItem) {
            $searchData->setSelectedContext($originalRoomItem->getTitle());
        }

        // if the top form submits a request it will call setPhrase() on SearchData
        $topForm = $this->createForm(SearchType::class, $searchData, [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId,
            ])
        ]);
        $topForm->handleRequest($request);

        // honor any sort arguments from the query URL
        $sortBy = $searchData->getSortBy();
        $sortOrder = $searchData->getSortOrder();
        $sortArguments = !empty($sortBy) && !empty($sortOrder) ? [$sortBy => $sortOrder] : [];

        /**
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        $this->setupSearchQueryConditions($searchManager, $searchData);
        $this->setupSearchFilterConditions($searchManager, $searchData, $roomId, $multipleContextFilterCondition, $readStatusFilterCondition);

        $searchResults = $searchManager->getResults($sortArguments);
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

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {

            $clickedButton = $filterForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            $savedSearch = $searchData->getSelectedSavedSearch();

            // NOTE: if a saved search was selected from the "Manage my views" dropdown, this performs a click (via an
            // `onchange` attribute) on the form's hidden "load" button; opposed to this, `$buttonName` will be empty
            // if the search params get changed for an existing saved search via the "Restrict results" form part
            if ($buttonName === 'load' && $savedSearch) {
                $savedSearchURL = $savedSearch->getSearchUrl();

                if ($savedSearchURL) {
                    // redirect to the search_url stored for the chosen saved search
                    $redirectResponse = new RedirectResponse($request->getSchemeAndHttpHost() . $savedSearchURL);

                    return $redirectResponse;
                }

            } elseif ($buttonName === 'delete' && $savedSearch) {
                $repository = $entityManager->getRepository(SavedSearch::class);
                $repository->removeSavedSearch($savedSearch);

                // remove the "delete" param as well as saved search related params from current search URL
                $request = $this->setSubParamForRequestQueryParam('delete', null, 'search_filter', $request);
                $request = $this->setSubParamForRequestQueryParam('selectedSavedSearch', null, 'search_filter', $request);
                $request = $this->setSubParamForRequestQueryParam('selectedSavedSearchTitle', null, 'search_filter', $request);
                $searchURL = $this->getUpdatedRequestUriForRequest($request);

                $redirectResponse = new RedirectResponse($request->getSchemeAndHttpHost() . $searchURL);

                return $redirectResponse;

            } elseif ($buttonName === 'save') {
                // this handles cases where the "Save" button (in the "Manage my views" form part) was clicked
                // with either "New view" or an existing saved search (aka "view") selected in the view dropdown

                if (!$savedSearch) { // create a new saved search
                    $savedSearch = new SavedSearch();
                    $portalUserId = $currentUser->getRelatedPortalUserItem()->getItemId();
                    $savedSearch->setAccountId($portalUserId);
                }

                $savedSearchTitle = $searchData->getSelectedSavedSearchTitle();
                if (empty($savedSearchTitle)) {
                    // this shouldn't get hit due to the validation annotation `@Assert\NotBlank(...)` for `SearchData->selectedSavedSearchTitle`
                    $savedSearchTitle = $translator->trans('New view', [], 'search');
                }
                if ($savedSearchTitle !== $savedSearch->getTitle()) {
                    $savedSearch->setTitle($savedSearchTitle);
                }

                // remove the "save" param from the search URL to be persisted
                $request = $this->setSubParamForRequestQueryParam('save', null, 'search_filter', $request);
                $savedSearchURL = $this->getUpdatedRequestUriForRequest($request);

                if ($savedSearchURL !== $savedSearch->getSearchUrl()) {
                    $savedSearch->setSearchUrl($savedSearchURL);
                }

                // for a newly created saved search, update its search URL with the correct ID
                if (empty($savedSearch->getId())) {
                    // persisting the new SavedSearch object will auto-assign an ID
                    $entityManager->persist($savedSearch);
                    $entityManager->flush();
                    $savedSearchId = $savedSearch->getId();

                    // update saved search ID in current search URL
                    $request = $this->setSubParamForRequestQueryParam('selectedSavedSearch', $savedSearchId, 'search_filter', $request);
                    $savedSearchURL = $this->getUpdatedRequestUriForRequest($request);

                    $savedSearch->setSearchUrl($savedSearchURL);
                }

                $entityManager->persist($savedSearch);
                $entityManager->flush();

                $redirectResponse = new RedirectResponse($request->getSchemeAndHttpHost() . $savedSearchURL);

                return $redirectResponse;
            }
        }

        $totalHits = $searchResults->getTotalHits();
        $results = $this->prepareResults($searchResults, $readerService, $roomId );

        return [
            'filterForm' => $filterForm->createView(),
            'roomId' => $roomId,
            'totalHits' => $totalHits,
            'results' => $results,
            'searchData' => $searchData,
            'isArchived' => $roomItem->isArchived(),
            'user' => $currentUser,
        ];
    }

    /**
     * Returns more search results
     *
     * @Route("/room/{roomId}/searchmore/{start}/{sort}")
     * @Template
     * @param Request $request
     * @param SearchManager $searchManager
     * @param MultipleContextFilterCondition $multipleContextFilterCondition
     * @param int $roomId
     * @param int $start
     * @return array
     */
    public function moreResultsAction(
        Request $request,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
        ReadStatusFilterCondition $readStatusFilterCondition,
        ReaderService $readerService,
        int $roomId,
        int $start = 0,
        $sort = ''
    )
    {
        // NOTE: to have the "load more" functionality work with any applied filters, we also need to add all
        //       SearchFilterType form fields to the "load more" query dictionary in results.html.twig

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request, $currentUser);

        /**
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        // honor sort field & order chosen by the user via the sort dropdown above the search results
        // NOTE: if $sort is set by feed.js, it contains a composite of the sort field & order (like 'title.raw__asc'
        // or 'creationDate__desc')
        if (!empty($sort)) {
            $sortArgs = explode('__', $sort);
            if (count($sortArgs) === 2) {
                $sortBy = $sortArgs[0];
                if (!empty($sortBy)) {
                    $searchData->setSortBy($sortBy);
                }
                $sortOrder = $sortArgs[1];
                if (!empty($sortOrder)) {
                    $searchData->setSortOrder($sortOrder);
                }
            }
        }
        // otherwise honor any pre-existing sortBy/sortOrder URL params
        $sortBy = $searchData->getSortBy();
        $sortOrder = $searchData->getSortOrder();
        $sortArguments = !empty($sortBy) && !empty($sortOrder) ? [$sortBy => $sortOrder] : [];

        $this->setupSearchQueryConditions($searchManager, $searchData);
        $this->setupSearchFilterConditions($searchManager, $searchData, $roomId, $multipleContextFilterCondition, $readStatusFilterCondition);

        $searchResults = $searchManager->getResults($sortArguments);
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

        $results = $this->prepareResults($searchResults, $readerService,  $roomId, $start);

        return [
            'roomId' => $roomId,
            'results' => $results,
            'user' => $currentUser,
        ];
    }

    /**
     * Populates the given SearchData object with relevant data from the request, and returns it.
     *
     * @param SearchData $searchData
     * @param Request $request
     * @param \cs_user_item $currentUser
     * @return SearchData
     */
    private function populateSearchData(
        SearchData $searchData,
        Request $request,
        \cs_user_item $currentUser
    ): SearchData
    {
        // TODO: should we better move this method to SearchData.php?

        if (!$request || !$currentUser) {
            return $searchData;
        }

        $requestParams = $request->query->all();
        if (empty($requestParams)) {
            $requestParams = $request->request->all();
        }

        // get all of the user's saved searches
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(SavedSearch::class);
        $portalUserId = $currentUser->getRelatedPortalUserItem()->getItemId();

        $savedSearches = $repository->findByAccountId($portalUserId);
        $searchData->setSavedSearches($savedSearches);

        if (empty($requestParams)) {
            return $searchData;
        }

        $searchParams = $requestParams['search_filter'] ?? $requestParams['search'] ?? null;

        // selected saved search parameters
        $savedSearchId = !empty($searchParams['selectedSavedSearch']) ? $searchParams['selectedSavedSearch'] : 0;
        if (!empty($savedSearchId)) {
            $savedSearch = $repository->findOneById($savedSearchId);
            $searchData->setSelectedSavedSearch($savedSearch);
        }

        $savedSearchTitle = $searchParams['selectedSavedSearchTitle'] ?? '';
        if (!empty($savedSearchTitle)) {
            $searchData->setSelectedSavedSearchTitle($savedSearchTitle);
        }

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

        // sortBy/sortOrder parameters
        $searchData->setSortBy($searchParams['sortBy'] ?? '');
        $searchData->setSortOrder($searchParams['sortOrder'] ?? 'asc');

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
     * @param Request $request
     * @param SearchManager $searchManager
     * @return JsonResponse The JSON result
     */
    public function roomNavigationAction(
        Request $request,
        SearchManager $searchManager,
        RouterInterface $router,
        TranslatorInterface $translator
    )
    {
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
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrCopyAction(
        Request $request,
        CopyAction $copyAction,
        int $roomId
    )
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $copyAction->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/search/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $deleteAction,
        int $roomId
    )
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $deleteAction->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    )
    {
        if ($selectAll) {
            // TODO: This is currently a limitation
            return [];
        } else {
            // TODO: This should be optimized
            $items = [];
            foreach ($itemIds as $itemId) {
                $items[] = $this->itemService->getTypedItem($itemId);
            }
            return $items;
        }
    }

    private function prepareResults(
        TransformedPaginatorAdapter $searchResults,
        ReaderService $readerService,
        int $currentRoomId,
        int $offset = 0,
        bool $json = false
    , $searchPhrase = null)
    {

        $results = [];
        foreach ($searchResults->getResults($offset, 10)->toArray() as $searchResult) {

            $reflection = new \ReflectionClass($searchResult);
            $type = strtolower(rtrim($reflection->getShortName(), 's'));

            if ($type === 'label') {
                $type = strtolower(rtrim($searchResult->getType(), 's'));
            }

            if ($json) {
                // construct target url
                $url = '#';

                $roomTitle = '';
                if ($type == 'room') {
                    $roomId = $currentRoomId;
                    $type = 'project';
                } else {
                    $roomId = $searchResult->getContextId();
                    $roomItem = $this->getRoom($roomId);
                    if ($roomItem) {
                        $roomTitle = $roomItem->getTitle();
                    }
                }

                $routeName = 'app_' . $type . '_detail';
                if ($this->router->getRouteCollection()->get($routeName)) {
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
                    'roomTitle' => $roomTitle,
                    'text' => $this->translator->trans(ucfirst($type), ['%count%' => 0], 'rubric'),
                    'url' => $url,
                    'value' => $searchResult->getItemId(),
                    'searchPhrase' => $searchPhrase ?? '',
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
                    $item = $this->itemService->getItem($searchResult->getItemId());
                    $readStatus = $readerService->cachedReadStatusForItem($item);
                }
                $results[] = [
                    'allowedActions' => $allowedActions,
                    'entity' => $searchResult,
                    'routeName' => 'app_' . $type . '_detail',
                    'files' => $this->itemService->getItemFileList($searchResult->getItemId()),
                    'type' => $type,
                    'status' => $status,
                    'readStatus' => $readStatus,
                ];
            }
        }

        return $results;
    }

    /**
     * Modifies & returns again the given Request object by setting (or removing) the sub-parameter with the given key
     * from the given query parameter key.
     *
     * @param string $subParamKey the key of the sub-parameter to be set or removed
     * @param string|null $subParamVal the value of the sub-parameter to be set; may be null in which case it will be removed
     * @param string $paramKey the query parameter key having the parameter with `$subParamKey` be set or reomoved
     * @param Request $request the Request object whose query params shall be modified
     * @return Request the modified Request object
     */
    private function setSubParamForRequestQueryParam(string $subParamKey, ?string $subParamVal, string $paramKey, Request $request): Request
    {
        if (empty($subParamKey) || empty($paramKey) || empty($request)) {
            return $request;
        }

        $queryBag = $request->query;

        /** @var array $subParams */
        $subParams = $queryBag->get($paramKey);
        if (!$subParams) {
            return $request;
        }

        if (!$subParamVal) {
            // null value: remove param if it exists
            if (!array_key_exists($subParamKey, $subParams)) {
                return $request;
            } else {
                unset($subParams[$subParamKey]);
            }
        } else {
            // set param
            $subParams[$subParamKey] = $subParamVal;
        }

        // update Request query params
        $queryBag->set($paramKey, $subParams);
        $request->query->replace($queryBag->all());

        return $request;
    }

    /**
     * Returns the request URI generated from the request's current path and query parameters.
     *
     * @return string The raw URI (i.e., not URI decoded)
     */
    private function getUpdatedRequestUriForRequest(Request $request): string
    {
        $pathInfo = $request->getPathInfo();

        // NOTE: w/o calling `overrideGlobals()`, `request()->getQueryString()` would return the original
        // query string ignoring the request's current path and query parameters
        $request->overrideGlobals();
        $queryString = $request->getQueryString();

        $requesthUri = $pathInfo . '?' . $queryString;

        return $requesthUri;
    }
}