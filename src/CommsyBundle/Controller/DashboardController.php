<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

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
            'roomItem' => $roomItem,
            'dashboardLayout' => $roomItem->getDashboardLayout(),
        );
    }

    
    /**
     * @Route("/dashboard/{itemId}/feed/{start}")
     * @Template()
     */
    public function feedAction($itemId, $max = 10, $start = 0)
    {
        // collect information for feed panel
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $userid=$legacyEnvironment->getCurrentUser()->getUserID();
        $dashboardFeedGenerator = $this->get('commsy.dashboard_feed_generator');
        $feedList = $dashboardFeedGenerator->getFeedList($userid, $max, $start);

        $userService = $this->get("commsy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        $tempFeedList = array();
        foreach ($feedList as $item) {
            if ($item != NULL) {
                $tempFeedList[] = $item;
                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                $reader = $readerService->getLatestReaderForUserByID($item->getItemId(), $relatedUser->getItemId());
                if ( empty($reader) ) {
                   $readerList[$item->getItemId()] = 'new';
                } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
                   $readerList[$item->getItemId()] = 'changed';
                }
            }
        }

        return array(
            'feedList' => $tempFeedList,
            'readerList' => $readerList
        );
    }
    
    /**
     * @Route("/dashboard/{roomId}/edit")
     */
    public function editAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $requestContent = json_decode($request->getContent());
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getPrivateRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $roomItem->setDashboardLayout($requestContent->data);
        $roomItem->save();
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->trans('dashboard changed');
        
        return new JsonResponse(array('message' => $message,
                                      'timeout' => '5550',
                                      'layout' => 'cs-notify-message',
                                      'data' => array(),
                                    ));
    }
}
