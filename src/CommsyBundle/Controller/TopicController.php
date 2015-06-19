<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class TopicController extends Controller
{
    /**
     * @Route("/room/{roomId}/topic/{topicId}")
     * @Template()
     */
    public function indexAction($roomId, $topicId, Request $request)
    {   
        return array();
    }

    /**
     * @Route("/room/{roomId}/topic")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
    	return array(
            'roomId' => $roomId
        );
    }
}
