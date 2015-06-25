<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

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
}