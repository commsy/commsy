<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class PersonController extends Controller
{
    /**
     * @Route("/room/{roomId}/person")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // get room user list
        $roomManager = $this->get("commsy.room_manager");

        $personList = $roomManager->getUserList($roomId);

        return array(
            'personList' => $personList
            );
    }
}
