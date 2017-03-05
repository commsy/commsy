<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\SearchType;
use CommsyBundle\Model\GlobalSearch;

use CommsyBundle\Filter\SearchFilterType;

class SearchController extends Controller
{
    /**
     * Generates the search form and search field for embedding them into
     * a template.
     *
     * @Template
     */
    public function searchFormAction($roomId, $linkSearch = false)
    {
        $form = $this->createForm(SearchType::class, [], [
            'action' => $this->generateUrl('commsy_search_results', [
                'roomId' => $roomId
            ])
        ]);

        return [
            'form' => $form->createView(),
            'roomId' => $roomId,
            'linkSearch' => $linkSearch
        ];
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

        $query = '';
        $filterData = [];

        $topForm = $this->createForm(SearchType::class, $globalSearch, [
            'action' => $this->generateUrl('commsy_search_results', [
                'roomId' => $roomId
            ])
        ]);

        $topForm->handleRequest($request);
        if ($topForm->isSubmitted() && $topForm->isValid()) {
            $globalSearch = $topForm->getData();
            $query = $globalSearch->getPhrase();

            $filterData['query'] = $query;
        }

        // $filterForm = $this->createForm(SearchFilterType::class, $filterData, [
        // ]);

        // $filterForm->handleRequest($request);
        // if ($filterForm->isSubmitted() && $filterForm->isValid()) {
        //     $filterFormData = $filterForm->getData();
        //     $query = $filterFormData['query'];
        // }

        $searchManager = $this->get('commsy.search.manager');
        $searchManager->setQuery($query);

        $searchResults = $searchManager->getResults();

        $totalHits = $searchResults->getTotalHits();
        $aggregations = $searchResults->getAggregations()['filterContext'];

        $contextBuckets = $aggregations['contexts']['buckets'];

        $results = [];
        foreach ($searchResults->getResults(0, 10)->toArray() as $searchResult) {

            $reflection = new \ReflectionClass($searchResult);
            $type = strtolower(rtrim($reflection->getShortName(), 's'));

            if ($type === 'label') {
                $type = strtolower(rtrim($searchResult->getType(), 's'));
            }

            $results[] = [
                'entity' => $searchResult,
                'routeName' => 'commsy_' . $type . '_detail',
            ];
        }

        return [
//            'filterForm' => $filterForm->createView(),
            'roomId' => $roomId,
            'totalHits' => $totalHits,
            'results' => $results,
//            'aggregations' => $aggregations,
            'query' => $query,
        ];
    }

    /**
     * Returns more search results
     * 
     * @Route("/room/{roomId}/searchmore/{query}/{start}/{sort}")
     * @Template
     */
    public function moreResultsAction($roomId, $query, $start = 0, $sort = 'date', Request $request)
    {
        $globalSearch = new GlobalSearch();

        $searchManager = $this->get('commsy.search.manager');
        $searchManager->setQuery($query);

        $searchResults = $searchManager->getResults();

        $totalHits = $searchResults->getTotalHits();
        $aggregations = $searchResults->getAggregations()['filterContext'];

        $contextBuckets = $aggregations['contexts']['buckets'];

        $results = [];
        foreach ($searchResults->getResults($start, 10)->toArray() as $searchResult) {
            $reflection = new \ReflectionClass($searchResult);
            $type = strtolower(rtrim($reflection->getShortName(), 's'));

            if ($type === 'label') {
                $type = strtolower(rtrim($searchResult->getType(), 's'));
            }

            $results[] = [
                'entity' => $searchResult,
                'routeName' => 'commsy_' . $type . '_detail',
            ];
        }

        return [
            'roomId' => $roomId,
            'results' => $results,
        ];
    }

    /**
     * Serves JSON results for instant search aka search-as-you-type
     * 
     * @Route("/room/{roomId}/search/instant/{linkSearch}")
     */
    public function instantAction($roomId, $linkSearch = false, Request $request)
    {
        $results = [];

        $query = $request->get('search', null);

        if ($query) {
            $translator = $this->get('translator');
            $router = $this->container->get('router');

            $searchManager = $this->get('commsy.search.manager');
            $searchManager->setQuery($query);

            // get every linked item id and add it to the query
            // to exclude this items from the resultlist
            if ($linkSearch) {
                $instantResults = $searchManager->getLinkedItemResults($roomId, $linkSearch);
            } else {
                $instantResults = $searchManager->getInstantResults();
            }

            foreach ($instantResults as $hybridResult) {
                $transformed = $hybridResult->getTransformed();

                $title = '';

                if (method_exists($transformed, 'getTitle')) {
                    $title = $transformed->getTitle();
                } else if (method_exists($transformed, 'getName')) {
                    $title = $transformed->getName();
                } else if (method_exists($transformed, 'getFirstname')) {
                    $title = $transformed->getFirstname() . ' ' . $transformed->getLastname();
                }

                // get type from hybrid results and trim trailing 's'
                $type = $hybridResult->getResult()->getType();
                $type = rtrim($type, 's');

                // construct target url
                $url = '#';

                $routeName = 'commsy_' . $type . '_detail';
                if ($router->getRouteCollection()->get($routeName)) {
                    $url = $this->generateUrl($routeName, [
                        'roomId' => $transformed->getContextId(),
                        'itemId' => $transformed->getItemId(),
                    ]);
                }

                $results[] = [
                    'title' => $title,
                    'text' => $translator->transChoice(ucfirst($type), 0, [], 'rubric'),
                    'url' => $url,
                    'value' => $transformed->getItemId(),
                ];
            }
        }

        $response = $this->autocompleteJsonStructure($results, $linkSearch);

        return $response;
    }

    /**
     * Serves JSON results with a specific structure for autocompletion
     * 
     */
    private function autocompleteJsonStructure($results, $linkSearch = false)
    {
        $response = new JsonResponse();
        // set json structure for uikit autocomplete
        if ($linkSearch) {
            $response->setData($results);
        } else {
            $response->setData([
                'results' => $results,
            ]);
        }

        return $response;
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
}