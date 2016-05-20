<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\SearchType;

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
        $form = $this->createForm(SearchType::class, [], [
            'action' => $this->generateUrl('commsy_search_results', [
                'roomId' => $roomId
            ])
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $query = $form->get('query')->getData();

            $searchManager = $this->get('commsy.search.manager');
            $searchManager->setQuery($query);
            $searchManager->setContext($roomId);

            $searchResults = $searchManager->getResults();

            dump($searchResults);
        }

        return [
            'searchResults' => $searchResults
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

            $searchManager = $this->get('commsy.search.manager');
            $searchManager->setQuery($query);
            $searchManager->setContext($roomId);

            $instantResults = $searchManager->getInstantResults();

            foreach ($instantResults as $instantResult) {
                $results[] = array(
                    'title' => $instantResult->getSearchTitle(),
                    'text' => '',
                    'url' => '#',
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