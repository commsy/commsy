<?php

namespace CommsyBundle\Controller;

use CommsyBundle\Form\Type\SearchItemType;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\SearchType;
use CommsyBundle\Model\GlobalSearch;

use CommsyBundle\Filter\SearchFilterType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class SearchController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class SearchController extends Controller
{
    /**
     * Generates the search form and search field for embedding them into
     * a template.
     * Post data needs to be passed directly, since we can not handle data
     * from the main request here.
     *
     * @Template
     */
    public function searchFormAction($roomId, $postData)
    {
        $globalSearch = new GlobalSearch();

        $form = $this->createForm(SearchType::class, $globalSearch, [
            'action' => $this->generateUrl('commsy_search_results', [
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
            'action' => $this->generateUrl('commsy_search_results', [
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
    public function itemSearchResultsAction($roomId, Request $request)
    {
        $query = $request->get('search', '');
        $searchManager = $this->get('commsy.search.manager');
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
    public function instantResultsAction($roomId, Request $request)
    {
        $query = $request->get('search', '');

        $searchManager = $this->get('commsy.search.manager');
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
    public function resultsAction($roomId, Request $request)
    {
        $globalSearch = new GlobalSearch();

        $filterData = [
            'all_rooms' => false,
            'query' => '',
        ];

        $topForm = $this->createForm(SearchType::class, $globalSearch, [
            'action' => $this->generateUrl('commsy_search_results', [
                'roomId' => $roomId,
            ])
        ]);
        $topForm->handleRequest($request);
        if ($topForm->isSubmitted() && $topForm->isValid()) {
            $globalSearch = $topForm->getData();
            $filterData['query'] = $globalSearch->getPhrase();
        }

        $filterForm = $this->createForm(SearchFilterType::class, $filterData, []);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted()) {
            $filterData = $filterForm->getData();
        }

        $searchManager = $this->getSearchManager($roomId, $filterData);

        $searchResults = $searchManager->getResults();
        $totalHits = $searchResults->getTotalHits();
        $results = $this->prepareResults($searchResults, $roomId);

        return [
            'filterForm' => $filterForm->createView(),
            'roomId' => $roomId,
            'totalHits' => $totalHits,
            'results' => $results,
            'query' => $filterData['query'],
        ];
    }

    /**
     * Returns more search results
     * 
     * @Route("/room/{roomId}/searchmore/{start}/{sort}")
     * @Template
     */
    public function moreResultsAction($roomId, $start = 0, $sort = 'date', Request $request)
    {
        $filterData = [
            'query' => $request->query->get('search', ''),
        ];
        $filterForm = $this->createForm(SearchFilterType::class, [], []);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted()) {
            $filterData = $filterForm->getData();
        }

        $searchManager = $this->getSearchManager($roomId, $filterData);

        $searchResults = $searchManager->getResults();
        $results = $this->prepareResults($searchResults, $roomId, $start);

        return [
            'roomId' => $roomId,
            'results' => $results,
        ];
    }

    /**
     * Generates JSON results for the room navigation search-as-you-type form
     *
     * @Route("/room/{roomId}/search/rooms")
     * 
     * @param  int $roomId The current room id
     * @return JsonResponse The JSON result
     */
    public function roomNavigationAction($roomId, Request $request)
    {
        $results = [];

        $query = $request->get('search', '');

        $router = $this->container->get('router');
        $translator = $this->container->get('translator');

        $searchManager = $this->get('commsy.search.manager');
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
            $routeName = 'commsy_room_home';
            if ($router->getRouteCollection()->get($routeName)) {
                $url = $this->generateUrl(
                    $routeName,
                    ['roomId' => $room->getItemId()]
                );
            }

            $results[] = [
                'title' => $room->getTitle(),
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

    private function prepareResults(TransformedPaginatorAdapter $searchResults, $currentRoomId, $offset = 0, $json = false)
    {
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

                $routeName = 'commsy_' . $type . '_detail';
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
                $results[] = [
                    'entity' => $searchResult,
                    'routeName' => 'commsy_' . $type . '_detail',
                ];
            }
        }

        return $results;
    }

    private function getSearchManager($roomId, $filterData)
    {
        $searchManager = $this->get('commsy.search.manager');

        if (isset($filterData['query'])) {
            $searchManager->setQuery($filterData['query']);
        }

        if (!isset($filterData['all_rooms']) || !$filterData['all_rooms']) {
            $searchManager->setContext($roomId);
        }

        return $searchManager;
    }
}