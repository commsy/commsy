<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\HomeFilterType;
use CommsyBundle\Form\Type\ModerationSupportType;

class RoomController extends Controller
{
    /**
     * @Route("/room/{roomId}")
     * @Template()
     */
    public function homeAction($roomId, Request $request)
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

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in feed generator
            $roomFeedGenerator = $this->get('commsy_legacy.room_feed_generator');
            $roomFeedGenerator->setFilterConditions($filterForm);
        }

        // ...and prepare some data
        $timeSpread = $roomItem->getTimeSpread();
        $numNewEntries = $roomItem->getNewEntries($timeSpread);
        $pageImpressions = $roomItem->getPageImpressions($timeSpread);
        
        $numActiveMember = $roomItem->getActiveMembers($timeSpread);
        $numTotalMember = $roomItem->getAllUsers();

/*        $numNewEntries = 0;
        $numActiveMember = 10;
        $numTotalMember = 1000;
        $pageImpressions = 1001;
*/

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

        $backgroundImage = $this->generateUrl("getBackground", array('roomId' => $roomId));

        // TODO: calculate parallax-scrolling range for home.html.twig depending on image dimensions!
        $roomService = $this->get('commsy.room_service');
        $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
        $filename = $saveDir . "/" . $roomItem->getBGImageFilename();
        $cover_height = 280;

        $serviceLinkExternal = $roomItem->getServiceLinkExternal();
        if ($serviceLinkExternal == '') {
           $portalItem = $legacyEnvironment->getCurrentPortalItem();
           if (isset($portalItem) and !empty($portalItem)) {
              $serviceLinkExternal = $portalItem->getServiceLinkExternal();
           }
           unset($portal_item);
        }
        if ($serviceLinkExternal == '') {
           $serverItem = $legacyEnvironment->getServerItem();
           $serviceLinkExternal = $serverItem->getServiceLinkExternal();
        }

        return array(
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
            'serviceLinkExternal' => $serviceLinkExternal,
        );
    }

    /**
     * @Route("/room/{roomId}/feed/{start}/{sort}")
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
     * @Route("/room/{roomId}/moderationsupport")
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
}
