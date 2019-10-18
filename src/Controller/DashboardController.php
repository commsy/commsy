<?php

namespace App\Controller;

use App\RoomFeed\RoomFeedGenerator;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DashboardController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard/{roomId}")
     * @Template()
     * @param ItemService $itemService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
     public function overviewAction(
         ItemService $itemService,
         LegacyEnvironment $environment,
         int $roomId
     ) {
        $legacyEnvironment = $environment->getEnvironment();

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
     * @param Request $request
     * @param ReaderService $readerService
     * @param RoomFeedGenerator $roomFeedGenerator
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $max
     * @return array
     */
    public function feedAction(
        Request $request,
        ReaderService $readerService,
        RoomFeedGenerator $roomFeedGenerator,
        LegacyEnvironment $legacyEnvironment,
        int $max = 10
    ) {
        $lastId = null;
        if ($request->query->has('lastId')) {
            $lastId = $request->query->get('lastId');
        }

        $feedList = $roomFeedGenerator->getDashboardFeedList($max, $lastId);

        $user = $legacyEnvironment->getEnvironment()->getPortalUserItem();

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
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return JsonResponse
     */
    public function editAction(
        Request $request,
        TranslatorInterface $translator,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $requestContent = json_decode($request->getContent());
        
        $legacyEnvironment = $environment->getEnvironment();

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
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function rssAction(
        Request $request,
        int $roomId
    ) {
        return array(
        );
    }

    /**
     * @Route("/dashboard/{roomId}/externalaccess")
     * @Template()
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function externalaccessAction(
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $user = $legacyEnvironment->getCurrentUser()->getRelatedPortalUserItem();

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
