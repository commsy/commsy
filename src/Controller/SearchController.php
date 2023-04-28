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
use App\Action\Mark\MarkAction;
use App\Entity\SavedSearch;
use App\Filter\SearchFilterType;
use App\Form\Type\SearchItemType;
use App\Form\Type\SearchType;
use App\Model\SearchData;
use App\Repository\SavedSearchRepository;
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
use App\Services\CalendarsService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use cs_item;
use cs_room_item;
use cs_user_item;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SearchController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class SearchController extends BaseController
{
    /**
     * SearchController constructor.
     */
    public function __construct(private readonly UrlGeneratorInterface $router)
    {
    }

    /**
     * Generates the search form and search field for embedding them into
     * a template.
     * Request data needs to be passed directly, since we can not handle data
     * from the main request here.
     */
    public function searchFormAction(
        int $roomId,
        $requestData,
        RoomService $roomService
    ): Response {
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
                'roomId' => $roomId,
            ]),
        ]);

        return $this->render('search/search_form.html.twig', [
            'form' => $form,
            'roomId' => $roomId,
            'originalRoomId' => $originalRoomId,
            'originalRoomTitle' => $originalRoomItem ? $originalRoomItem->getTitle() : '',
        ]);
    }

    /**
     * @param $roomId int The id of the containing context
     */
    public function itemSearchFormAction(
        int $roomId
    ): Response {
        $form = $this->createForm(SearchItemType::class, [], [
            'action' => $this->generateUrl('app_search_results', [
                'roomId' => $roomId,
            ]),
        ]);

        return $this->render('search/item_search_form.html.twig', [
            'form' => $form,
            'roomId' => $roomId,
        ]);
    }

    /**
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/search/itemresults')]
    public function itemSearchResultsAction(
        Request $request,
        SearchManager $searchManager,
        ReaderService $readerService,
        CalendarsService $calendarsService,
        int $roomId
    ): Response {
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
        $results = $this->prepareResults($searchResults, $readerService, $calendarsService, $roomId, 0, true);

        $response = new JsonResponse();

        $response->setData($results);

        return $response;
    }

    /**
     * @param $roomId int The context id
     *
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/search/instantresults')]
    public function instantResultsAction(
        Request $request,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
        ReaderService $readerService,
        CalendarsService $calendarsService,
        int $roomId
    ): Response {
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
        $results = $this->prepareResults($searchResults, $readerService, $calendarsService, $roomId, 0, true, $query);

        $response = new JsonResponse();

        $response->setData([
            'results' => $results,
        ]);

        return $response;
    }

    /**
     * Displays search results.
     */
    #[Route(path: '/room/{roomId}/search/results')]
    public function resultsAction(
        Request $request,
        RoomService $roomService,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
        ReadStatusFilterCondition $readStatusFilterCondition,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ReaderService $readerService,
        CalendarsService $calendarsService,
        SavedSearchRepository $savedSearchRepository,
        int $roomId
    ): Response {
        // NOTE: for a guest user, $roomItem may be null (e.g. when initiating a search from "all rooms")
        $roomItem = $roomService->getRoomItem($roomId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request, $currentUser, $savedSearchRepository);

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
            ]),
        ]);
        $topForm->handleRequest($request);

        // honor any sort arguments from the query URL
        $sortBy = $searchData->getSortBy();
        $sortOrder = $searchData->getSortOrder();
        $sortArguments = !empty($sortBy) && !empty($sortOrder) ? [$sortBy => $sortOrder] : [];

        /*
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
        if (!empty($selectedRubric) && 'all' !== $selectedRubric && !array_key_exists($selectedRubric, $countsByRubric)) {
            $searchData->addRubrics([$selectedRubric => 0]);
        }

        $selectedCreator = $searchData->getSelectedCreator();
        if (!empty($selectedCreator) && 'all' !== $selectedCreator && !array_key_exists($selectedCreator, $countsByCreator)) {
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
        if (!empty($selectedContext) && 'all' !== $selectedContext && !array_key_exists($selectedContext, $countsByContext)) {
            $searchData->addContexts([$selectedContext => 0]);
        }

        $selectedTodoStatus = $searchData->getSelectedTodoStatus();
        if (!empty($selectedTodoStatus) && 0 !== $selectedTodoStatus && !array_key_exists($selectedTodoStatus, $countsByTodoStatus)) {
            $searchData->addTodoStatuses([$selectedTodoStatus => 0]);
        }

        // if the filter form is submitted by a GET request we use the same data object here to populate the data
        $filterForm = $this->createForm(SearchFilterType::class, $searchData, [
            'contextId' => $roomId,
            'userIsReallyGuest' => $currentUser->isReallyGuest(),
        ]);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $clickedButton = $filterForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            // NOTE: the "Manage my views" form part isn't available for guest users
            if (!$currentUser->isReallyGuest()) {
                $savedSearch = $searchData->getSelectedSavedSearch();

                // NOTE: if a saved search was selected from the "Manage my views" dropdown, this performs a click (via an
                // `onchange` attribute) on the form's hidden "load" button; opposed to this, `$buttonName` will be empty
                // if the search params get changed for an existing saved search via the "Filter results" form part
                if ('load' === $buttonName && $savedSearch) {
                    $savedSearchURL = $savedSearch->getSearchUrl();

                    if ($savedSearchURL) {
                        // redirect to the search_url stored for the chosen saved search
                        return $this->redirect($request->getSchemeAndHttpHost() . $savedSearchURL);
                    }
                } elseif ('delete' === $buttonName && $savedSearch) {
                    $repository = $entityManager->getRepository(SavedSearch::class);
                    $repository->removeSavedSearch($savedSearch);

                    // remove the "delete" param as well as saved search related params from current search URL
                    $request = $this->setSubParamForRequestQueryParam('delete', null, 'search_filter', $request);
                    $request = $this->setSubParamForRequestQueryParam('selectedSavedSearch', null, 'search_filter', $request);
                    $request = $this->setSubParamForRequestQueryParam('selectedSavedSearchTitle', null, 'search_filter', $request);
                    $searchURL = $this->getUpdatedRequestUriForRequest($request);

                    return $this->redirect($request->getSchemeAndHttpHost() . $searchURL);
                } elseif ('save' === $buttonName) {
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

                    return $this->redirect($request->getSchemeAndHttpHost() . $savedSearchURL);
                }
            }
        }

        $totalHits = $searchResults->getTotalHits();
        $results = $this->prepareResults($searchResults, $readerService, $calendarsService, $roomId);

        return $this->render('search/results.html.twig', [
            'filterForm' => $filterForm,
            'roomId' => $roomId,
            'totalHits' => $totalHits,
            'results' => $results,
            'searchData' => $searchData,
            'isArchived' => $roomItem ? $roomItem->getArchived() : false,
            'user' => $currentUser,
        ]);
    }

    /**
     * Returns more search results.
     */
    #[Route(path: '/room/{roomId}/searchmore/{start}/{sort}')]
    public function moreResultsAction(
        Request $request,
        SearchManager $searchManager,
        MultipleContextFilterCondition $multipleContextFilterCondition,
        ReadStatusFilterCondition $readStatusFilterCondition,
        ReaderService $readerService,
        CalendarsService $calendarsService,
        SavedSearchRepository $savedSearchRepository,
        int $roomId,
        int $start = 0,
        string $sort = ''
    ): Response {
        // NOTE: to have the "load more" functionality work with any applied filters, we also need to add all
        //       SearchFilterType form fields to the "load more" query dictionary in results.html.twig

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $searchData = new SearchData();
        $searchData = $this->populateSearchData($searchData, $request, $currentUser, $savedSearchRepository);

        /*
         * Before we build the SearchFilterType form we need to get the current aggregations from ElasticSearch
         * according to the current query parameters.
         */

        // honor sort field & order chosen by the user via the sort dropdown above the search results
        // NOTE: if $sort is set by feed.js, it contains a composite of the sort field & order (like 'title.raw__asc'
        // or 'creationDate__desc')
        if (!empty($sort)) {
            $sortArgs = explode('__', $sort);
            if (2 === count($sortArgs)) {
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
            'userIsReallyGuest' => $currentUser->isReallyGuest(),
        ]);
        $filterForm->handleRequest($request);

        $results = $this->prepareResults($searchResults, $readerService, $calendarsService, $roomId, $start);

        return $this->render('search/more_results.html.twig', [
            'roomId' => $roomId,
            'results' => $results,
            'user' => $currentUser,
        ]);
    }

    /**
     * Populates the given SearchData object with relevant data from the request, and returns it.
     */
    private function populateSearchData(
        SearchData $searchData,
        Request $request,
        cs_user_item $currentUser,
        SavedSearchRepository $savedSearchRepository
    ): SearchData {
        // TODO: should we better move this method to SearchData.php?

        if (!$request || !$currentUser) {
            return $searchData;
        }

        $requestParams = $request->query->all();
        if (empty($requestParams)) {
            $requestParams = $request->request->all();
        }

        // get all of the user's saved searches
        $portalUser = $currentUser->getRelatedPortalUserItem();
        if ($portalUser) {
            $savedSearches = $savedSearchRepository->findByAccountId($portalUser->getItemId());
            $searchData->setSavedSearches($savedSearches);
        }

        if (empty($requestParams)) {
            return $searchData;
        }

        $searchParams = $requestParams['search_filter'] ?? $requestParams['search'] ?? null;

        // selected saved search parameters
        $savedSearchId = !empty($searchParams['selectedSavedSearch']) ? $searchParams['selectedSavedSearch'] : 0;
        if (!empty($savedSearchId)) {
            $savedSearch = $savedSearchRepository->findOneById($savedSearchId);
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
        $searchData->setSelectedContext($searchParams['selectedContext'] ?? 'all');

        // appearing in parameter (based on Lexik\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType)
        $searchData->setAppearsIn($searchParams['appears_in'] ?? []);

        // read status parameter
        $searchData->setSelectedReadStatus($searchParams['selectedReadStatus'] ?? 'all');

        // rubric parameter
        $searchData->setSelectedRubric($searchParams['selectedRubric'] ?? 'all');

        // creator parameter
        $searchData->setSelectedCreator($searchParams['selectedCreator'] ?? 'all');

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
                $date = DateTime::createFromFormat('d.m.Y', $searchParams['creation_date_range']['left_date']);
                if ($date) {
                    $date = $date->setTime(0, 0, 0);
                    $creationDateRange[0] = $date;
                }
            }
            if (!empty($searchParams['creation_date_range']['right_date'])) {
                $date = DateTime::createFromFormat('d.m.Y', $searchParams['creation_date_range']['right_date']);
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
                $date = DateTime::createFromFormat('d.m.Y', $searchParams['modification_date_range']['left_date']);
                if ($date) {
                    $date = $date->setTime(0, 0, 0);
                    $modificationDateRange[0] = $date;
                }
            }
            if (!empty($searchParams['modification_date_range']['right_date'])) {
                $date = DateTime::createFromFormat('d.m.Y', $searchParams['modification_date_range']['right_date']);
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
        if (empty($selectedReadStatus) || 'all' === $selectedReadStatus) {
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
        if ('todo' === $searchData->getSelectedRubric() && $searchData->getSelectedTodoStatus()) {
            $todoStatusFilterCondition = new TodoStatusFilterCondition();
            $todoStatusFilterCondition->setTodoStatus($searchData->getSelectedTodoStatus());
            $searchManager->addFilterCondition($todoStatusFilterCondition);
        }
    }

    /**
     * Generates JSON results for the room navigation search-as-you-type form.
     *
     * @return JsonResponse The JSON result
     */
    #[Route(path: '/room/{roomId}/search/rooms')]
    public function roomNavigationAction(
        Request $request,
        SearchManager $searchManager,
        RouterInterface $router,
        TranslatorInterface $translator
    ): Response {
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

        $rooms = [...$rooms['community'], ...$rooms['project'], ...$rooms['grouproom'], ...$rooms['userroom']];

        $lastType = null;
        foreach ($rooms as $room) {
            $url = '#';

            if (!$lastType || $lastType != $room->getType()) {
                if (in_array($room->getType(), ['project', 'community', 'userroom'])) {
                    $title = $translator->trans(ucfirst((string) $room->getType()).' Rooms', [], 'room');
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
                'title' => html_entity_decode((string) $room->getTitle()),
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

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/search/xhr/mark', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkAction(
        Request $request,
        MarkAction $markAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markAction->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/search/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $deleteAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $deleteAction->execute($room, $items);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_item[]
     */
    public function getItemsByFilterConditions(
        $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
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
        CalendarsService $calendarsService,
        int $currentRoomId,
        int $offset = 0,
        bool $json = false,
        $searchPhrase = null
    ) {
        $results = [];
        foreach ($searchResults->getResults($offset, 10)->toArray() as $searchResult) {
            $reflection = new ReflectionClass($searchResult);
            $type = strtolower(rtrim($reflection->getShortName(), 's'));

            if ('label' === $type) {
                $type = strtolower(rtrim((string) $searchResult->getType(), 's'));
            }

            if ($json) {
                // construct target url
                $url = '#';

                $roomTitle = '';
                if ('room' == $type) {
                    $roomId = $currentRoomId;
                    $type = 'project';
                } else {
                    $roomId = $searchResult->getContextId();
                    $roomItem = $this->getRoom($roomId);
                    if ($roomItem) {
                        $roomTitle = $roomItem->getTitle();
                    }
                }

                if ('room' === $type) {
                    $routeName = 'app_roomall_detail';
                } else {
                    $routeName = 'app_'.$type.'_detail';
                }

                $portalId = $this->legacyEnvironment->getCurrentPortalID();

                if ($this->router->getRouteCollection()->get($routeName)) {
                    $url = $this->generateUrl($routeName, [
                        'portalId' => $portalId,
                        'roomId' => $roomId,
                        'itemId' => $searchResult->getItemId(),
                    ]);
                }

                $title = '';

                if (method_exists($searchResult, 'getTitle')) {
                    $title = $searchResult->getTitle();
                } elseif (method_exists($searchResult, 'getName')) {
                    $title = $searchResult->getName();
                } elseif (method_exists($searchResult, 'getFirstname')) {
                    $title = $searchResult->getFirstname().' '.$searchResult->getLastname();
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
                $allowedActions = ['mark'];
                if (method_exists($searchResult, 'getItemId')) {
                    if ($this->isGranted('ITEM_EDIT', $searchResult->getItemId()) && ('user' !== $type)) {
                        $allowedActions[] = 'delete';
                    }
                }
                // handle Date entities representing date items from external calendar sources
                $isExternal = false;
                if (method_exists($searchResult, 'getExternal')) {
                    $isExternal = $searchResult->getExternal();
                }
                $calendar = null;
                if (method_exists($searchResult, 'getCalendarId')) {
                    $calendarId = $searchResult->getCalendarId();
                    $calendars = $calendarsService->getCalendar($calendarId);
                    $calendar = !empty($calendars) ? $calendars[0] : null;
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

                if ('room' === $type) {
                    $routeName = 'app_roomall_detail';
                } else {
                    $routeName = 'app_'.$type.'_detail';
                }

                $portalId = $this->legacyEnvironment->getCurrentPortalID();

                $results[] = [
                    'allowedActions' => $allowedActions,
                    'entity' => $searchResult,
                    'routeName' => $routeName,
                    'files' => $this->itemService->getItemFileList($searchResult->getItemId()),
                    'type' => $type,
                    'status' => $status,
                    'readStatus' => $readStatus,
                    'isExternal' => $isExternal,
                    'calendar' => $calendar,
                    'portalId' => $portalId,
                ];
            }
        }

        return $results;
    }

    /**
     * Modifies & returns again the given Request object by setting (or removing) the sub-parameter with the given key
     * from the given query parameter key.
     *
     * @param string      $subParamKey the key of the sub-parameter to be set or removed
     * @param string|null $subParamVal the value of the sub-parameter to be set; may be null in which case it will be removed
     * @param string      $paramKey    the query parameter key having the parameter with `$subParamKey` be set or reomoved
     * @param Request     $request     the Request object whose query params shall be modified
     *
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

        $requesthUri = $pathInfo.'?'.$queryString;

        return $requesthUri;
    }
}
