<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Commsy\LegacyBundle\Services\UserService;
use Commsy\LegacyBundle\Services\ReaderService;
use CommsyBundle\Filter\HomeFilterType;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard/{roomId}")
     * @Template()
     */
     public function overviewAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getPrivateRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        
        $roomFeedGenerator = $this->get('commsy.dashboard_feed_generator');

        return array(
            'roomItem' => $roomItem);
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

        $userService = $this->get("commsy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($feedList as $item) {
            $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
            $reader = $readerService->getLatestReaderForUserByID($item->getItemId(), $relatedUser->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'feedList' => $feedList,
            'readerList' => $readerList
        );
    }    
}
