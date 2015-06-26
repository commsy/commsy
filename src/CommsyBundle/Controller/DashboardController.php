<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $user = $legacyEnvironment->getCurrentUser();
        return array(
            'user' => $user
        );
    }
    
    /**
     * @Route("/dashboard/{itemId}feed/{start}")
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
