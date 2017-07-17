<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\HomeFilterType;
use CommsyBundle\Form\Type\ModerationSupportType;
use CommsyBundle\Filter\RoomFilterType;

class RoomController extends Controller
{
    /**
     * @Route("/room/{roomId}", requirements={
     *     "roomId": "\d+"
     * })
     * @Template()
     */
    public function homeAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createForm(HomeFilterType::class, null, array(
            'action' => $this->generateUrl('commsy_room_home', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        $header = "latest entries";

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in feed generator
            $roomFeedGenerator = $this->get('commsy_legacy.room_feed_generator');
            $roomFeedGenerator->setFilterConditions($filterForm);
            $header = "search results";
        }

        // ...and prepare some data
        $timeSpread = $roomItem->getTimeSpread();
        $numNewEntries = $roomItem->getNewEntries($timeSpread);
        $pageImpressions = $roomItem->getPageImpressions($timeSpread);
        
        $numActiveMember = $roomItem->getActiveMembers($timeSpread);
        $numTotalMember = $roomItem->getAllUsers();

        $moderators = array();
        $moderatorList = $roomItem->getModeratorList();
        $moderatorUserItem = $moderatorList->getFirst();
        while ($moderatorUserItem) {
            $moderators[] = $moderatorUserItem;
            $moderatorUserItem = $moderatorList->getNext();
        }

        $announcementManager = $legacyEnvironment->getAnnouncementManager();
        $announcementManager->setContextLimit($roomId);
        $announcementManager->setDateLimit(getCurrentDateTimeInMySQL());
        $countAnnouncements = $announcementManager->getCountAll();

        $backgroundImage = null;
        if($roomItem->getBGImageFilename())
            $backgroundImage = $this->generateUrl("getBackground", array('roomId' => $roomId, 'imageType' => 'custom'));            
        else
            $backgroundImage = $this->generateUrl("getBackground", array('roomId' => $roomId, 'imageType' => 'theme'));

        $logoImage = null;
        if($roomItem->getLogoFilename()) {
            $logoImage = $this->generateUrl("getLogo", array('roomId' => $roomId));
        }

        // TODO: calculate parallax-scrolling range for home.html.twig depending on image dimensions!
        $roomService = $this->get('commsy_legacy.room_service');
        $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);

        // support mail
        $serviceContact = [
            'show' => false,
        ];
        $portalItem = $roomItem->getContextItem();
        if ($portalItem->showServiceLink()) {
            $serviceContact['show'] = true;
            $serviceContact['email'] = $portalItem->getServiceEmail();
        }

        // RSS-Feed / iCal / Wiki
        $rss = [
            'show' => false,
            'url' => $this->generateUrl('commsy_rss', [
                'contextId' => $roomId,
            ]),
        ];

        $wiki = [
            'show' => false,
            'url' => str_ireplace('[COMMSY_CONTEXT_ID]', $roomItem->getItemId(), $this->getParameter('commsy.mediawiki.roomWikiUrl')),
        ];

        if (!$roomItem->isLocked() && !$roomItem->isClosed()) {
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();

            if ($roomItem->isRSSOn()) {
                $rss['show'] = true;
            }

            if ($roomItem->isWikiEnabled()) {
                $wiki['show'] = true;
            }

            if (!$roomItem->isOpenForGuests()) {
                if ($currentUserItem->isUser()) {
                    $hashManager = $legacyEnvironment->getHashManager();

                    $rss['url'] = $this->generateUrl('commsy_rss', [
                        'contextId' => $roomId,
                        'hid' => $hashManager->getRSSHashForUser($currentUserItem->getItemID()),
                    ]);

                    $wiki['url'] = $wiki['url'].'?session-id='.$legacyEnvironment->getSessionID();
                }
            }
        }

        // home information text
        $homeInformationEntry = null;
        if ($roomItem->withInformationBox()) {
            $entryId = $roomItem->getInformationBoxEntryID();
            $itemService = $this->get('commsy_legacy.item_service');
            $homeInformationEntry = $itemService->getTypedItem($entryId);
        }

        return [
            'homeInformationEntry' => $homeInformationEntry,
            'form' => $filterForm->createView(),
            'roomItem' => $roomItem,
            'timeSpread' => $timeSpread,
            'numNewEntries' => $numNewEntries,
            'pageImpressions' => $pageImpressions,
            'numActiveMember' => $numActiveMember,
            'numTotalMember' => $numTotalMember,
            'roomModerators' => $moderators,
            'showCategories' => $roomItem->withTags(),
            'countAnnouncements' => $countAnnouncements,
            'bgImageFilepath' => $backgroundImage,
            'logoImageFilepath' => $logoImage,
            'serviceContact' => $serviceContact,
            'rss' => $rss,
            'wiki' => $wiki,
            'header' => $header,
        ];
    }

    /**
     * @Route("/room/{roomId}/feed/{start}/{sort}", requirements={
     *     "roomId": "\d+"
     * })
     * @Template("CommsyBundle:Room:list.html.twig")
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createForm(HomeFilterType::class, null, array(
            'action' => $this->generateUrl('commsy_room_home', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // collect information for feed panel
        $roomFeedGenerator = $this->get('commsy_legacy.room_feed_generator');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in feed generator
            $roomFeedGenerator->setFilterConditions($filterForm);
        }

        $feedList = $roomFeedGenerator->getFeedList($roomId, $max, $start);
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerService = $this->get('commsy_legacy.reader_service');

 
        $readerList = array();
        foreach ($feedList as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        return array(
            'feedList' => $feedList,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive()
         );
    }
    
    /**
     * @Route("/room/{roomId}/moderationsupport", requirements={
     *     "roomId": "\d+"
     * })
     * @Template()
     */
    public function moderationsupportAction($roomId, Request $request)
    {
        $moderationsupportData = array();
        $form = $this->createForm(ModerationSupportType::class, $moderationsupportData, array(
            'action' => $this->generateUrl('commsy_room_moderationsupport', array(
                'roomId' => $roomId,
            ))
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

            $currentUser = $legacyEnvironment->getCurrentUser();

            $roomManager = $legacyEnvironment->getRoomManager();
            $roomItem = $roomManager->getItem($roomId);
            
            $moderatorEmailAdresses = array();
            $moderatorList = $roomItem->getModeratorList();
            $moderatorUserItem = $moderatorList->getFirst();
            while ($moderatorUserItem) {
                $moderatorEmailAdresses[$moderatorUserItem->getEmail()] = $moderatorUserItem->getFullname();
                $moderatorUserItem = $moderatorList->getNext();
            }
            
            $message = \Swift_Message::newInstance()
                ->setSubject($data['subject'])
                ->setFrom(array($currentUser->getEmail() => $currentUser->getFullname()))
                ->setTo($moderatorEmailAdresses)
                ->setBody($data['message'])
            ;
            
            $message->setCc(array($currentUser->getEmail() => $currentUser->getFullname()));
            
            $this->get('mailer')->send($message);
            
            $translator = $this->get('translator');
            
            return new JsonResponse([
                'message' => $translator->trans('message was send'),
                'timeout' => '5550',
                'layout' => 'cs-notify-message',
                'data' => array(),
            ]);
        }
        
        return array(
            'form' => $form->createView(),
        );
    }

    /**
     *
     * @Route("/room/{roomId}/all", requirements={
     *     "roomId": "\d+"
     * })
     * @Template()
     * 
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listAllAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $filterForm = $this->createForm(RoomFilterType::class, null, [
            'showTime' => $portalItem->showTime(),
            'timePulses' => $roomService->getTimePulses(),
        ]);

        $filterForm->handleRequest($request);

        $count = 0;
        $countAll = 0;

        // ***** Active rooms *****
        $repository = $this->getDoctrine()->getRepository('CommsyBundle:Room');
        $activeRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId());
        $activeRoomQueryBuilder->select($activeRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll += $activeRoomQueryBuilder->getQuery()->getSingleScalarResult();

        // filtered rooms
        if ($filterForm->isValid()) {
            $this->get('lexik_form_filter.query_builder_updater')
                ->addFilterConditions($filterForm, $activeRoomQueryBuilder);
            $count += $activeRoomQueryBuilder->getQuery()->getSingleScalarResult();
        }
        else {
            $count = $countAll;
        }

        // ***** Archived rooms *****
        // TODO: Refactoring needed
        // We need to change the repository when querying archived rooms.
        // This is not the best solution, but works for now. It would be better
        // to use the form validation below, instead of manually checking for a
        // specific value
        $repository = $this->getDoctrine()->getRepository('CommsyBundle:ZzzRoom');
        $archivedRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId());
        $archivedRoomQueryBuilder->select($archivedRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll += $archivedRoomQueryBuilder->getQuery()->getSingleScalarResult();

        if ($request->query->has('room_filter')) {
            $roomFilter = $request->query->get('room_filter');

            // "archived" not set or archived != 1 = include archived rooms in list 
            if (!isset($roomFilter['archived']) || $roomFilter['archived'] != "1") {
                if ($filterForm->isValid()) {
                    $this->get('lexik_form_filter.query_builder_updater')
                        ->addFilterConditions($filterForm, $archivedRoomQueryBuilder);
                    $count += $archivedRoomQueryBuilder->getQuery()->getSingleScalarResult();
                }
            }
        }
        // archived rooms have to be included if they aren't explicitely excluded!
        else {
            $this->get('lexik_form_filter.query_builder_updater')
                ->addFilterConditions($filterForm, $archivedRoomQueryBuilder);
            $count += $archivedRoomQueryBuilder->getQuery()->getSingleScalarResult();
        }

        if ($legacyEnvironment->isArchiveMode()) {
            $legacyEnvironment->deactivateArchiveMode();
        }

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'itemsCountArray' => [
                'count' => $count,
                'countAll' => $countAll,
            ],
        ];
    }

    /**
     * @Route("/room/{roomId}/all/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAllAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $roomFilter = $request->get('roomFilter');
        if (!$roomFilter) {
            $roomFilter = $request->query->get('room_filter');
        }

        // ***** Active rooms *****
        $repository = $this->getDoctrine()->getRepository('CommsyBundle:Room');
        $activeRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId());
        $activeRoomQueryBuilder->setMaxResults($max);
        $activeRoomQueryBuilder->setFirstResult($start);

        if ($roomFilter) {
            $filterForm = $this->createForm(RoomFilterType::class, $roomFilter, [
                'showTime' => $portalItem->showTime(),
                'timePulses' => $roomService->getTimePulses(),
            ]);

            // manually bind values from the request
            $filterForm->submit($roomFilter);

            $this->get('lexik_form_filter.query_builder_updater')
                    ->addFilterConditions($filterForm, $activeRoomQueryBuilder);
        }

        $rooms = $activeRoomQueryBuilder->getQuery()->getResult();

        // ***** Archived rooms *****
        if(!$roomFilter || !isset($roomFilter['archived']) || $roomFilter['archived'] != "1") {
            $legacyEnvironment->activateArchiveMode();
            $repository = $this->getDoctrine()->getRepository('CommsyBundle:ZzzRoom');
            $archivedRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId());
            $archivedRoomQueryBuilder->setMaxResults($max);
            $archivedRoomQueryBuilder->setFirstResult($start);

            if ($roomFilter) {
                $filterForm = $this->createForm(RoomFilterType::class, $roomFilter, [
                    'showTime' => $portalItem->showTime(),
                    'timePulses' => $roomService->getTimePulses(),
                ]);
                $filterForm->submit($roomFilter);
                $this->get('lexik_form_filter.query_builder_updater')
                        ->addFilterConditions($filterForm, $archivedRoomQueryBuilder);
            }

            $rooms = array_merge($rooms, $archivedRoomQueryBuilder->getQuery()->getResult());
        }

        if ($legacyEnvironment->isArchiveMode()) {
            $legacyEnvironment->deactivateArchiveMode();
        }

        return [
            'portal' => $portalItem,
            'rooms' => $rooms,
        ];
    }

    /**
     * @Route("/room/{roomId}/logo")
     * @Template()
     */
    public function logoAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        return [
            'portalUrl' => $request->getSchemeAndHttpHost() . '?cid=' . $portalItem->getItemId(),
        ];
    }
}
