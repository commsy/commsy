<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\SearchType;
use CommsyBundle\Model\GlobalSearch;

use Elastica\Query\Filtered;
use Elastica\Query\MatchPhrasePrefix;
use Elastica\Filter\Term;
use Elastica\Filter\Range;
use Elastica\Filter\Bool;

class SearchController extends Controller
{
    /**
     * Generates the search form and search field for embedding them into
     * a template.
     *
     * @Template
     */
    public function searchFormAction($roomId)
    {
        $form = $this->createForm(SearchType::class, [], [
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
     * Displays search results
     * 
     * @Route("/room/{roomId}/search/results")
     * @Template
     */
    public function resultsAction($roomId, Request $request)
    {
        $globalSearch = new GlobalSearch();

        $form = $this->createForm(SearchType::class, $globalSearch, [
            'action' => $this->generateUrl('commsy_search_results', [
                'roomId' => $roomId
            ])
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $globalSearch = $form->getData();

            $searchManager = $this->get('commsy.search.manager');
            $searchManager->setQuery($globalSearch->getPhrase());

            $searchResults = $searchManager->getResults();

            dump($searchResults->getResults(0, 100)->toArray());
            dump($searchResults->getAggregations());
        }

        return [
            'searchResults' => $searchResults->getResults(0, 100)->toArray()
        ];
    }

    /**
     * Serves JSON results for instant search aka search-as-you-type
     * 
     * @Route("/room/{roomId}/search/instant")
     * @Template
     */
    public function instantAction($roomId, Request $request)
    {
        $results = [];

        $query = $request->get('search', null);

        if ($query) {
            $translator = $this->get('translator');

            $searchManager = $this->get('commsy.search.manager');
            $searchManager->setQuery($query);

            $instantResults = $searchManager->getInstantResults();

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

                $router = $this->container->get('router');
                $routeName = 'commsy_' . $type . '_detail';
                if ($router->getRouteCollection()->get($routeName)) {
                    $url = $this->generateUrl(
                        $routeName,
                        ['roomId' => $roomId, 'itemId' => $transformed->getItemId()]
                    );
                }

                $results[] = array(
                    'title' => $title,
                    'text' => $translator->transChoice($type, 0, [], 'rubric'),
                    'url' => $url,
                );
            }
        }

        $response = new JsonResponse();
        $response->setData([
            'results' => $results,
        ]);

        return $response;
    }
}