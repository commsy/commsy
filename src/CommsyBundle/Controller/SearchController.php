<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Elastica\Query\Filtered;
use Elastica\Query\MatchPhrasePrefix;
use Elastica\Filter\Term;
use Elastica\Filter\Range;
use Elastica\Filter\Bool;

class SearchController extends Controller
{
    /**
    * @Route("/room/{roomId}/search/")
    * @Template
    */
    public function searchAction($roomId, Request $request)
    {
        $finder = $this->get('fos_elastica.finder.commsy.user');
        $searchTerm = $request->query->get('search');
        $searchTerm = "chris";
        $user = $finder->find($searchTerm);
        dump($user);
        return array('user' => $user);
    }

    /**
    * @Route("/room/{roomId}/search/quick")
    * @Template
    */
    public function quickAction($roomId, Request $request)
    {
        $results = array(
        );

        $query = $request->get('search', null);

        if ($query) {

            $searchBuilder = $this->get('commsy.search_builder');
            $searchBuilder->setQuery($query);
            $searchBuilder->setContext($roomId);

            //
            $searchBuilder->setRubric('material');
            //
            
            $materials = $searchBuilder->getResults();

            $dataset = array();

            foreach ($materials as $material) {
                // $username = $user->getFirstname() . ' ' . $user->getLastname();

                $dataset = array(
                    'title' => $material->getTitle(),
                    'text' => '',
                    'url' => '#',
                );

                $results[] = $dataset;
            }
        }

        $response = new JsonResponse();
        $response->setData(array(
            'results' => $results,
        ));

        return $response;
    }
}