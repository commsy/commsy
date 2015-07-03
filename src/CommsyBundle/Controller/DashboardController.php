<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Commsy\LegacyBundle\Services\UserService;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard/{roomId}")
     * @Template()
     */
    public function indexAction($roomId, Request $request)
    {
        $userService = $this->get("commsy.user_service");
        $user = $userService->getPortalUserFromSessionId();
        return array(
            'user' => $user
        );
    }
    
    /**
     * @Route("/dashboard/{itemId}/feed/{start}")
     * @Template()
     */
    public function feedAction($itemId, $max = 10, $start = 0)
    {
        // collect information for feed panel
        $dashboardFeedGenerator = $this->get('commsy.dashboard_feed_generator');
        $feedList = $dashboardFeedGenerator->getFeedList($itemId, $max, $start);

        return array(
            'feedList' => $feedList
        );
    }    
}
