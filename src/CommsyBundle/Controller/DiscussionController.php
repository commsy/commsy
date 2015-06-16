<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DiscussionController extends Controller
{
    /**
     * @Route("/room/{roomId}/discussion")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // get discussion list
        // $roomManager = $this->get("commsy.room_manager");

        // $personArray = $roomManager->getUserList($roomId);

        return array(
            // 'personArray' => $personArray
        );
    }
}
