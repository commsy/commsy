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
        
        $roomFeedGenerator = $this->get('commsy_legacy.dashboard_feed_generator');

        return array(
            'roomItem' => $roomItem,
            'dashboardLayout' => $roomItem->getDashboardLayout(),
        );
    }

    
    /**
     * @Route("/dashboard/{roomId}/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0)
    {
        // collect information for feed panel
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $userId = $legacyEnvironment->getCurrentUser()->getUserID();
        $dashboardFeedGenerator = $this->get('commsy_legacy.dashboard_feed_generator');
        $feedList = $dashboardFeedGenerator->getFeedList($userId, $max, $start);

        $userService = $this->get("commsy_legacy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $feedItems = [];
        foreach ($feedList as $item) {
            if ($item != null) {
                $feedItems[] = $item;

                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                $reader = $readerService->getLatestReaderForUserByID($item->getItemId(), $relatedUser->getItemId());
                if (empty($reader)) {
                    $readerList[$item->getItemId()] = 'new';
                } elseif ($reader['read_date'] < $item->getModificationDate()) {
                    $readerList[$item->getItemId()] = 'changed';
                }
            }
        }

        return [
            'feedList' => $feedItems,
            'readerList' => $readerList
        ];
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
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->trans('dashboard changed', [], 'messages');
        
        return new JsonResponse(array('message' => $message,
                                      'timeout' => '5550',
                                      'layout' => 'cs-notify-message',
                                      'data' => array(),
                                    ));
    }
    
    /**
     * @Route("/dashboard/{roomId}/rss")
     * @Template()
     */
    public function rssAction($roomId, Request $request)
    {
        return array(
        );
    }
    
    /**
     * @Route("/dashboard/{roomId}/externalaccess")
     * @Template()
     */
    public function externalaccessAction($roomId, Request $request)
    {
        $userService = $this->get("commsy_legacy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $itemManager = $legacyEnvironment->getItemManager();
        $releasedIds = $itemManager->getExternalViewerEntriesForRoom($roomId);
        $viewableIds = $itemManager->getExternalViewerEntriesForUser($user->getUserID());
        
        $releasedItems = array();
        foreach ($releasedIds as $releasedId) {
            $tempItem = $itemManager->getItem($releasedId);
            $tempManager = $legacyEnvironment->getManager($tempItem->getItemType());
            $releasedItems[] = $tempManager->getItem($releasedId);
        }
        
        $viewableItems = array();
        foreach ($viewableIds as $viewableId) {
            $tempItem = $itemManager->getItem($viewableId);
            $tempManager = $legacyEnvironment->getManager($tempItem->getItemType());
            $viewableItems[] = $tempManager->getItem($viewableId);
        }
        
        return array(
            'releaseItems' => $releasedItems,
            'viewableItems' => $viewableItems
        );
    }
}
