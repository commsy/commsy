<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DateController extends Controller
{    
    /**
     * @Route("/room/{roomId}/date/{dateId}")
     * @Template()
     */
    public function indexAction($roomId, $dateId, Request $request)
    {   
        return array();
    }

    /**
     * @Route("/room/{roomId}/date")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
    	return array(
            'roomId' => $roomId
        );
    }
}
