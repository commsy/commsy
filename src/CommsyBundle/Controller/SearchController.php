<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Elastica\Query\Filtered;
use Elastica\Query\MatchAll;
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
            $finder = $this->get('fos_elastica.finder.commsy.user');

            $boolFilter = new Bool();

            $contextTerm = new Term();
            $contextTerm->setTerm('contextId', $roomId);
            $boolFilter->addMust($contextTerm);

            $boolFilter->addMust(new Range('status', array('gte' => 2)));

            $filteredQuery = new Filtered();
            $filteredQuery->setQuery(new MatchAll());
            $filteredQuery->setFilter($boolFilter);

            

            $users = $finder->find($filteredQuery);

            $dataset = array();

            foreach ($users as $user) {
                $username = $user->getFirstname() . ' ' . $user->getLastname();

                $dataset = array(
                    'title' => $username,
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