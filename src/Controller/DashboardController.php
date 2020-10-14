<?php

namespace App\Controller;

use App\Services\ReaderService;
use App\Services\UserService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DashboardController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
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

        // iCal
        $iCal = [
            'show' => false,
            'aboUrl' => $this->generateUrl('app_ical_getcontent', [
                'contextId' => $roomId,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'exportUrl' => $this->generateUrl('app_ical_getcontent', [
                'contextId' => $roomId,
                'export' => true,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if ($roomItem->isOpenForGuests()) {
            $iCal['show'] = true;
        } else {
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();

            if ($currentUserItem->isUser()) {
                $iCal['show'] = true;

                $hashManager = $legacyEnvironment->getHashManager();
                $iCalHash = $hashManager->getICalHashForUser($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $iCalHash,
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $user = $legacyEnvironment->getCurrentUserItem();
        $userList = $user->getRelatedUserList()->to_array();
        $contextIds = array();
        foreach ($userList as $user) {
            $contextIds[] = $user->getContextId();
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');
        $calendars = $repository->findBy(array('context_id' => $contextIds, 'external_url' => array('', NULL)));

        $itemService = $this->get('commsy_legacy.item_service');
        $contextArray = [];
        foreach ($calendars as $index => $calendar) {
            $roomItemCalendar = $itemService->getTypedItem($calendar->getContextId());
            $contextArray[$calendar->getContextId()][] = $roomItemCalendar->getTitle();
        }

        // announcements
        $portalItem = $legacyEnvironment->getCurrentPortalItem();
        $serverItem = $legacyEnvironment->getServerItem();

        // given the current portal configuration, is the current user allowed to create new rooms?
        $userMayCreateContext = false;
        $currentUser = $legacyEnvironment->getCurrentUser();
        if (!$currentUser->isRoot()) {
            $portalUser = $currentUser->getRelatedPortalUserItem();

            if ($portalUser) {
                if ($portalUser->isModerator()) {
                    $userMayCreateContext = true;
                } else if ($portalItem->getCommunityRoomCreationStatus() == 'all' || $portalItem->getProjectRoomCreationStatus() == 'portal') {
                    $userMayCreateContext = $currentUser->isAllowedToCreateContext();
                }
            }
        } else {
            $userMayCreateContext = true;
        }

        return array(
            'roomItem' => $roomItem,
            'dashboardLayout' => $roomItem->getDashboardLayout(),
            'iCal' => $iCal,
            'calendars' => $calendars,
            'contextArray' => $contextArray,
            'portal' => $portalItem,
            'server' => $serverItem,
            'userMayCreateContext' => $userMayCreateContext,
        );
    }

    
    /**
     * @Route("/dashboard/{roomId}/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        $lastId = null;
        if ($request->query->has('lastId')) {
            $lastId = $request->query->get('lastId');
        }

        $roomFeedGenerator = $this->get('commsy.room_feed_generator');
        $feedList = $roomFeedGenerator->getDashboardFeedList($max, $lastId);

        $userService = $this->get("commsy_legacy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $feedItems = [];
        foreach ($feedList as $item) {
            if ($item != null) {
                $feedItems[] = $item;

                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                $readerList[$item->getItemId()] = $readerService->getChangeStatusForUserByID($item->getItemId(), $relatedUser->getItemId());
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
    public function myViews($roomId, Request $request)
    {
        $userService = $this->get("commsy_legacy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $itemManager = $legacyEnvironment->getItemManager();

        $myViewItems = ['Konfigurierte Ansicht 1', 'Konfigurierte Ansicht 2'];

        return array(
            'myViewItems' => $myViewItems,
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
            if ($tempItem) {
                $tempManager = $legacyEnvironment->getManager($tempItem->getItemType());
                $releasedItems[] = $tempManager->getItem($releasedId);
            }
        }
        
        $viewableItems = array();
        foreach ($viewableIds as $viewableId) {
            $tempItem = $itemManager->getItem($viewableId);
            if ($tempItem) {
                $tempManager = $legacyEnvironment->getManager($tempItem->getItemType());
                $viewableItems[] = $tempManager->getItem($viewableId);
            }
        }
        
        return array(
            'releaseItems' => $releasedItems,
            'viewableItems' => $viewableItems
        );
    }
}
