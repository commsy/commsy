<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Entity\ZzzRoom;
use App\Filter\HomeFilterType;
use App\Filter\RoomFilterType;
use App\Form\Type\ContextType;
use App\Form\Type\ModerationSupportType;
use App\Services\LegacyMarkup;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RoomController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class RoomController extends Controller
{
    /**
     * @Route("/room/{roomId}", requirements={
     *     "roomId": "\d+"
     * })
     * @Template()
     */
    public function homeAction($roomId, Request $request, LegacyMarkup $legacyMarkup)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        // fall back on default theme if rooms theme is not supported anymore
        if ($roomItem && !in_array($roomItem->getColorArray()['schema'], $this->container->getParameter('liip_theme.themes'))) {
            $roomItem->setColorArray(array('schema' => 'default'));
            $roomItem->save();
        }

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createForm(HomeFilterType::class, null, array(
            // on submit, reload the room's home page & bring again the filter form into view (via the #list-filter anchor)
            'action' => $this->generateUrl('app_room_home', array(
                'roomId' => $roomId,
                '_fragment' => 'list-filter',
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        $header = "latest entries";

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in feed generator
            $roomFeedGenerator = $this->get('commsy.room_feed_generator');
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

        // support mail
        $serviceContact = [
            'show' => false,
        ];
        $portalItem = $roomItem->getContextItem();
        if ($portalItem->showServiceLink()) {
            $serviceContact['show'] = true;
            $serviceContact['link'] = $roomService->buildServiceLink();
        }

        // RSS-Feed / iCal / Wiki
        $rss = [
            'show' => false,
            'url' => $this->generateUrl('app_rss', [
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

                    $rss['url'] = $this->generateUrl('app_rss', [
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

            // This check is now present in settings form. Check also added here to secure display of rooms with old and invalid settings in database.
            if (!in_array($homeInformationEntry->getItemType(), [CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE])) {
                $roomItem->setwithInformationBox(false);
                $homeInformationEntry = null;
            } else {
                $itemService = $this->get('commsy_legacy.item_service');
                $legacyMarkup->addFiles($itemService->getItemFileList($homeInformationEntry->getItemId()));
            }
        }

        $userTasks = $this->getDoctrine()->getRepository(User::class)->getConfirmableUserByContextId($roomId)->getQuery()->getResult();

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
            'isModerator' => $legacyEnvironment->getCurrentUserItem()->isModerator(),
            'userTasks' => $userTasks,
            'deletesRoomIfUnused' => $portalItem->isActivatedDeletingUnusedRooms(),
            'daysUnusedBeforeRoomDeletion' => $portalItem->getDaysUnusedBeforeDeletingRooms(),
        ];
    }

    /**
     * @Route("/room/{roomId}/feed/{start}/{sort}", requirements={
     *     "roomId": "\d+"
     * })
     * @Template("room/list.html.twig")
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
            'action' => $this->generateUrl('app_room_home', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // collect information for feed panel
        $roomFeedGenerator = $this->get('commsy.room_feed_generator');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in feed generator
            $roomFeedGenerator->setFilterConditions($filterForm);
        }

        $lastId = null;
        if ($request->query->has('lastId')) {
            $lastId = $request->query->get('lastId');
        }

        $feedList = $roomFeedGenerator->getRoomFeedList($roomId, $max, $lastId);
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
            'action' => $this->generateUrl('app_room_moderationsupport', array(
                'roomId' => $roomId,
            ))
        ));
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
            
            $message = (new \Swift_Message())
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
        $repository = $this->getDoctrine()->getRepository(Room::class);
        $activeRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId());
        $activeRoomQueryBuilder->select($activeRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll += $activeRoomQueryBuilder->getQuery()->getSingleScalarResult();

        // filtered rooms
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
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
        $repository = $this->getDoctrine()->getRepository(ZzzRoom::class);
        $archivedRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId());
        $archivedRoomQueryBuilder->select($archivedRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll += $archivedRoomQueryBuilder->getQuery()->getSingleScalarResult();

        if ($request->query->has('room_filter')) {
            $roomFilter = $request->query->get('room_filter');

            // "archived" not set or archived != 1 = include archived rooms in list 
            if (!isset($roomFilter['archived']) || $roomFilter['archived'] != "1") {
                if ($filterForm->isSubmitted() && $filterForm->isValid()) {
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

        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'itemsCountArray' => [
                'count' => $count,
                'countAll' => $countAll,
            ],
            'userMayCreateContext' => $userMayCreateContext,
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
        $repository = $this->getDoctrine()->getRepository('App:Room');
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
            $repository = $this->getDoctrine()->getRepository('App:ZzzRoom');
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

        $projectsMemberStatus = array();
        foreach ($rooms as $room) {

            try{
                $projectsMemberStatus[$room->getItemId()] = $this->memberStatus($room);
                $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();
                $users = $currentPortalItem->getUserList();
                $contactUsers = $room->getContactPersons();
                foreach($users as $user){
                    if(strpos($contactUsers, $user->getFullName()) !== false){
                        $contactItemId = $user->getItemID();
                        if($user->isCommSyContact()) {
                            $room->setContactPersons($contactUsers . ";" . $contactItemId);
                            break;
                        }
                    }
                }
            }catch (Exception $e){
                // do nothing
            }
        }
        return [
            'roomId' => $roomId,
            'portal' => $portalItem,
            'rooms' => $rooms,
            'projectsMemberStatus' => $projectsMemberStatus,
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

    /**
     * @Route("/room/{roomId}/all/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function detailAction($roomId, $itemId, Request $request, LegacyMarkup $legacyMarkup)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $userService = $this->get('commsy_legacy.user_service');

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        $currentUser = $legacyEnvironment->getCurrentUser();

        $infoArray = $this->getDetailInfo($roomItem);

        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);

        $showRoomModerationActions = false;
        $roomUser = $currentUser->getRelatedUserItemInContext($itemId);
        if ($currentUser->isRoot() || (isset($roomUser) && $roomUser->isModerator())) {
            $showRoomModerationActions = true;
        } else {
            $portalUser = $currentUser->getRelatedPortalUserItem();
            if ($portalUser && $portalUser->isModerator()) {
                $showRoomModerationActions = true;
            }
        }

        $roomService = $this->get('commsy_legacy.room_service');
        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);

        $itemService = $this->get('commsy_legacy.item_service');
        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        return [
            'roomId' => $roomId,
            'item' => $roomItem,
            'currentUser' => $currentUser,
            'modifierList' => $infoArray['modifierList'],
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'memberStatus' => $memberStatus,
            'contactModeratorItems' => $contactModeratorItems,
            'showRoomModerationActions' => $showRoomModerationActions,
            'portalId' => $legacyEnvironment->getCurrentPortalItem()->getItemId(),
        ];
    }

    private function getDetailInfo($room)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();

        $info = [];

        // modifier
        $info['modifierList'][$room->getItemId()] = $itemService->getAdditionalEditorsForItem($room);

        // total user count
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $userList = $userManager->get();

        $info['userCount'] = $userList->getCount();

        // total and since modification reader count
        $readerCount = 0;
        $readSinceModificationCount = 0;
        $currentUser = $userList->getFirst();

        $userIds = array();
        while ($currentUser) {
            $userIds[] = $currentUser->getItemID();

            $currentUser = $userList->getNext();
        }

        $readerManager->getLatestReaderByUserIDArray($userIds, $room->getItemID());
        $currentUser = $userList->getFirst();
        while ($currentUser) {
            $currentReader = $readerManager->getLatestReaderForUserByID($room->getItemID(), $currentUser->getItemID());
            if ( !empty($currentReader) ) {
                if ($currentReader['read_date'] >= $room->getModificationDate()) {
                    $readSinceModificationCount++;
                }

                $readerCount++;
            }
            $currentUser = $userList->getNext();
        }

        $info['readCount'] = $readerCount;
        $info['readSinceModificationCount'] = $readSinceModificationCount;

        return $info;
    }

    /**
     * @Route("/room/{roomId}/all/{itemId}/request", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function requestAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        // determine form options
        $formOptions = [
            'checkNewMembersWithCode' => false,
            'withAGB' => false,
        ];

        if ($roomItem->checkNewMembersWithCode()) {
            $formOptions['checkNewMembersWithCode'] = $roomItem->getCheckNewMemberCode();
        }

        $agbText = '';
        if ($roomItem->getAGBStatus() != 2) {
            $formOptions['withAGB'] = true;

            // get agb text in users language
            $agbText = $roomItem->getAGBTextArray()[strtoupper($legacyEnvironment->getUserLanguage())];
        }

        $form = $this->createForm(ContextRequestType::class, null, $formOptions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('request')->isClicked()) {
                $formData = $form->getData();

                // At this point we can assume that the user has accepted agb and
                // provided the correct code if necessary (or provided no code at all).
                // We can now build a new user item and set the appropriate status

                $currentUserItem = $legacyEnvironment->getCurrentUserItem();
                $privateRoomUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();

                if ($privateRoomUserItem) {
                    $newUser = $privateRoomUserItem->cloneData();
                    $newPicture = $privateRoomUserItem->getPicture();
                } else {
                    $newUser = $currentUserItem->cloneData();
                    $newPicture = $currentUserItem->getPicture();
                }

                $newUser->setContextID($roomItem->getItemID());

                if (!empty($newPicture)) {
                    $values = explode('_', $newPicture);
                    $values[0] = 'cid' . $newUser->getContextID();

                    $newPictureName = implode('_', $values);

                    $discManager = $legacyEnvironment->getDiscManager();
                    $discManager->copyImageFromRoomToRoom($newPicture, $newUser->getContextID());
                    $newUser->setPicture($newPictureName);
                }

                if ($formData['description']) {
                    $newUser->setUserComment($formData['description']);
                }

                if ($roomItem->checkNewMembersAlways() ||
                    ($roomItem->checkNewMembersWithCode() && !isset($formData['code']))) {
                    // The user either needs to ask for access or provided no code
                    $newUser->request();
                    $isRequest = true;
                } else {
                    // no authorization is needed at all or the code was correct
                    $newUser->makeUser();
                    $isRequest = false;

                    // link user with group "all"
                    $groupManager = $legacyEnvironment->getLabelManager();
                    $groupManager->setExactNameLimit('ALL');
                    $groupManager->setContextLimit($roomItem->getItemID());
                    $groupManager->select();
                    $groupList = $groupManager->get();
                    $group = $groupList->getFirst();

                    if ($group) {
                        $group->addMember($newUser);
                    }
                }

                if ($roomItem->getAGBStatus()) {
                    $newUser->setAGBAcceptance();
                }

                // check if user id already exists
                $userTestItem = $roomItem->getUserByUserID($newUser->getUserID(), $newUser->getAuthSource());
                if (!$userTestItem && !$newUser->isReallyGuest() && !$newUser->isRoot()) {
                    $newUser->save();
                    $newUser->setCreatorID2ItemID();

                    // save task
                    if ($isRequest) {
                        $taskManager = $legacyEnvironment->getTaskManager();
                        $taskItem = $taskManager->getNewItem();

                        $taskItem->setCreatorItem($currentUserItem);
                        $taskItem->setContextID($roomItem->getItemID());
                        $taskItem->setTitle('TASK_USER_REQUEST');
                        $taskItem->setStatus('REQUEST');
                        $taskItem->setItem($newUser);
                        $taskItem->save();
                    }

                    // mail to moderators
                    $message = (new \Swift_Message())
                        ->setFrom([$this->getParameter('commsy.email.from') => $roomItem->getContextItem()->getTitle()])
                        ->setReplyTo([$newUser->getEmail() => $newUser->getFullName()]);

                    $userManager = $legacyEnvironment->getUserManager();
                    $userManager->resetLimits();
                    $userManager->setModeratorLimit();
                    $userManager->setContextLimit($roomItem->getItemID());
                    $userManager->select();

                    $moderatorList = $userManager->get();
                    $moderator = $moderatorList->getFirst();
                    $moderators = '';
                    while ($moderator) {
                        if ($moderator->getAccountWantMail() == 'yes') {
                            $message->addTo($moderator->getEmail(), $moderator->getFullname());
                            $moderators .= $moderator->getFullname() . "\n";
                        }

                        $moderator = $moderatorList->getNext();
                    }

                    // language
                    $language = $roomItem->getLanguage();
                    if ($language == 'user') {
                        $language = $newUser->getLanguage();
                        if ($language == 'browser') {
                            $language = $legacyEnvironment->getSelectedLanguage();
                        }
                    }

                    $translator = $legacyEnvironment->getTranslationObject();

                    if ($message->getTo()) {
                        $savedLanguage = $translator->getSelectedLanguage();
                        $translator->setSelectedLanguage($language);

                        $message->setSubject($translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT', $newUser->getFullname(), $roomItem->getTitle()));

                        $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(date("Y-m-d H:i:s")), $translator->getTimeInLang(date("Y-m-d H:i:s")));
                        $body .= "\n\n";

                        if ($legacyEnvironment->getCurrentPortalItem()->getHideAccountname()) {
                            $userId = 'XXX ' . $translator->getMessage('COMMON_DATASECURITY');
                        } else {
                            $userId = $newUser->getUserID();
                        }
                        if (!$roomItem->isGroupRoom()) {
                            $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        } else {
                            $body .= $translator->getMessage('GROUPROOM_USER_JOIN_CONTEXT_MAIL_BODY', $newUser->getFullname(), $userId, $newUser->getEmail(), $roomItem->getTitle());
                        }
                        $body .= "\n\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                        } else {
                            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                        }
                        $body .= "\n\n";

                        if ($formData['description']) {
                            $body .= $translator->getMessage('MAIL_COMMENT_BY', $newUser->getFullname(), $formData['description']);
                            $body .= "\n\n";
                        }

                        $body .= $translator->getMessage('MAIL_SEND_TO', $moderators);
                        $body .= "\n";

                        if ($isRequest) {
                            $body .= $translator->getMessage('MAIL_USER_FREE_LINK') . "\n";
                            $body .= $this->generateUrl('app_user_list', [
                                'roomId' => $roomItem->getItemID(),
                                'user_filter' => [
                                    'user_status' => 1,
                                ],
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                        } else {
                            $body .= $this->generateUrl('app_room_home', [
                                'roomId' => $roomItem->getItemID(),
                            ], UrlGeneratorInterface::ABSOLUTE_URL);
                        }

                        $message->setBody($body, 'text/plain');

                        $this->get('mailer')->send($message);

                        $translator->setSelectedLanguage($savedLanguage);
                    }
                }

                // inform user if request required no authorization
                if ($newUser->isUser()) {
                    $moderatorList = $roomItem->getModeratorList();
                    $contactModerator = $moderatorList->getFirst();

                    $translator = $legacyEnvironment->getTranslationObject();
                    $translator->setEmailTextArray($roomItem->getEmailTextArray());
                    $translator->setContext('project');

                    $savedLanguage = $translator->getSelectedLanguage();

                    $language = $roomItem->getLanguage();
                    if ($language == 'user') {
                        $language = $newUser->getLanguage();
                        if ($language == 'browser') {
                            $language = $legacyEnvironment->getSelectedLanguage();
                        }
                    }

                    if ($legacyEnvironment->getCurrentPortalItem()->getHideAccountname()) {
                        $userId = 'XXX ' . $translator->getMessage('COMMON_DATASECURITY');
                    } else {
                        $userId = $newUser->getUserID();
                    }

                    $translator->setSelectedLanguage($language);

                    $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER', $roomItem->getTitle());

                    $body  = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(date("Y-m-d H:i:s")), $translator->getTimeInLang(date("Y-m-d H:i:s")));
                    $body .= "\n\n";
                    $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $newUser->getFullname());
                    $body .= "\n\n";
                    if ($roomItem->isCommunityRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_GR', $userId, $roomItem->getTitle());
                    } else if ($roomItem->isProjectRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_PR', $userId, $roomItem->getTitle());
                    } else if ($roomItem->isGroupRoom()) {
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER_GP', $userId, $roomItem->getTitle());
                    }
                    $body .= "\n\n";
                    $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $contactModerator->getFullname(), $roomItem->getTitle());
                    $body .= "\n\n";
                    $body .= $this->generateUrl('app_room_home', [
                        'roomId' => $roomItem->getItemID(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $message = (new \Swift_Message())
                        ->setSubject($subject)
                        ->setBody($body, 'text/plain')
                        ->setFrom([$this->getParameter('commsy.email.from') => $roomItem->getContextItem()->getTitle()])
                        ->setReplyTo([$contactModerator->getEmail() => $contactModerator->getFullName()])
                        ->setTo([$newUser->getEmail()]);

                    $this->get('mailer')->send($message);

                    $translator->setSelectedLanguage($savedLanguage);
                }
            }

            // redirect to detail page
            $route = "";
            if ($roomItem->isGroupRoom()) {
                $route = $this->redirectToRoute('app_group_detail', [
                    'roomId' => $roomId,
                    'itemId' => $roomItem->getLinkedGroupItemID(),
                ]);
            }
            else {
                $route = $this->redirectToRoute('app_project_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }
            return $route;
        }

        return [
            'form' => $form->createView(),
            'agbText' => $agbText,
            'title' => $roomItem->getTitle(),
        ];
    }

    /**
     * @param $roomId
     * @param Request $request
     *
     * @Route("/room/{roomId}/all/create", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');
        $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();

        $type = null;
        $context = $request->get('context');
        if ($context) {
            if (isset($context['type_select'])) {
                $type = $context['type_select'];
            }
        }

        // NOTE: `getDefault...TemplateID()` may also return '-1' (if no default template is defined)
        $defaultId = '-1';
        if ($type === 'project') {
            $defaultId = $currentPortalItem->getDefaultProjectTemplateID();
        }
        elseif ($type === 'community') {
            $defaultId = $currentPortalItem->getDefaultCommunityTemplateID();
        }
        $defaultTemplateIDs = ($defaultId === '-1') ? [] : [ $defaultId ];

        $timesDisplay = $currentPortalItem->getCurrentTimeName();
        $times = $roomService->getTimePulses(true);

        $current_user = $legacyEnvironment->getCurrentUserItem();
        $community_list = $currentPortalItem->getCommunityList();
        $community_room_array = array();
        unset($temp_array);
        if ($community_list->isNotEmpty()) {
            $community_item = $community_list->getFirst();
            while ($community_item) {
                if ($community_item->isAssignmentOnlyOpenForRoomMembers() ){
                    if ( $community_item->isUser($current_user)) {
                        $community_room_array[$community_item->getTitle()] = $community_item->getItemID();
                    }
                }else{
                    $community_room_array[$community_item->getTitle()] = $community_item->getItemID();
                }
                $community_item = $community_list->getNext();
            }
        }

        $types = [];
        $portalUser = $current_user->getRelatedPortalUserItem();

        if ($portalUser->isModerator()) {
            $types = ['project' => 'project', 'community' => 'community'];
        } else {
            $roomItem = $roomService->getRoomItem($roomId);

            if ($currentPortalItem->getProjectRoomCreationStatus() == 'portal') {
                $types['project'] = 'project';
            } else if ($roomItem->getType() == CS_COMMUNITY_TYPE) {
                $types['project'] = 'project';
            }

            if ($currentPortalItem->getCommunityRoomCreationStatus() == 'all') {
                $types['community'] = 'community';
            }
        }

        $linkCommunitiesMandantory = true;
        if ($currentPortalItem->getProjectRoomLinkStatus() == 'optional') {
            $linkCommunitiesMandantory = false;
        }

        $roomCategoriesService = $this->get('commsy.roomcategories_service');
        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($currentPortalItem->getItemId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }

        $linkRoomCategoriesMandatory = $currentPortalItem->isTagMandatory() && count($roomCategories) > 0;

        $templates = $this->getAvailableTemplates($type);

        // necessary, since the data field malfunctions when added via listener call (#2979)
        $templates['No template'] = '-1';

        // re-sort array by elements
        foreach($templates as $index => $entry){
            if(!($index == 'No template')){
                unset($templates[$index]);
                $templates[$index] = $entry;
            }
        }

        $formData = [];
        $form = $this->createForm(ContextType::class, $formData, [
            'types' => $types,
            'templates' => $templates,
            'preferredChoices' => $defaultTemplateIDs,
            'timesDisplay' => $timesDisplay,
            'times' => $times,
            'communities' => $community_room_array,
            'linkCommunitiesMandantory' => $linkCommunitiesMandantory,
            'roomCategories' => $roomCategories,
            'linkRoomCategoriesMandatory' => $linkRoomCategoriesMandatory,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            if ($form->get('save')->isClicked() && isset($formData['type_select'])) {
                if ($formData['type_select'] == 'project') {
                    $roomManager = $legacyEnvironment->getProjectManager();
                }
                elseif ($formData['type_select'] == 'community') {
                     $roomManager = $legacyEnvironment->getCommunityManager();
                }
                else {
                    throw new UnexpectedValueException("Error Processing Request: Unrecognized room type", 1);
                }
                $legacyRoom = $roomManager->getNewItem();

                $currentUser = $legacyEnvironment->getCurrentUserItem();
                $legacyRoom->setCreatorItem($currentUser);
                $legacyRoom->setCreationDate(getCurrentDateTimeInMySQL());
                $legacyRoom->setModificatorItem($currentUser);
                $legacyRoom->setContextID($legacyEnvironment->getCurrentPortalID());
                $legacyRoom->open();

                if ($formData['type_select'] == 'project' && isset($context['type_sub']['community_rooms'])) {
                    $legacyRoom->setCommunityListByID($context['type_sub']['community_rooms']);
                }

                // fill in form values from the new entity object
                $legacyRoom->setTitle($context['title']);
                $legacyRoom->setDescription($context['room_description']);

                $timeIntervals = (isset($context['type_sub']['time_interval'])) ? $context['type_sub']['time_interval'] : [];
                if (empty($timeIntervals) || in_array('cont', $timeIntervals)) {
                    $legacyRoom->setContinuous();
                    $legacyRoom->setTimeListByID([]);
                } else {
                    $legacyRoom->setNotContinuous();
                    $legacyRoom->setTimeListByID($timeIntervals);
                }

                // persist with legacy code
                $legacyRoom->save();

                $calendarsService = $this->get('commsy.calendars_service');
                $calendarsService->createCalendar($legacyRoom, null, null, true);

                // take values from a template?
                if (isset($context['type_sub']['master_template'])) {
                    $masterRoom = $roomService->getRoomItem($context['type_sub']['master_template']);
                    if ($masterRoom) {
                        $legacyRoom = $this->copySettings($masterRoom, $legacyRoom);
                    }
                }

                // NOTE: we can only set the language after copying settings from any room template, otherwise the language
                // would get overwritten by the room template's language setting
                $legacyRoom->setLanguage($context['language']);
                $legacyRoom->save();

                // mark the room as edited
                $linkModifierItemManager = $legacyEnvironment->getLinkModifierItemManager();
                $linkModifierItemManager->markEdited($legacyRoom->getItemID());

                if (isset($context['categories'])) {
                    $roomCategoriesService->setRoomCategoriesLinkedToContext($legacyRoom->getItemId(), $context['categories']);
                }

                // redirect to the project detail page
                return $this->redirectToRoute('app_room_detail', [
                    'roomId' => $roomId,
                    'itemId' => $legacyRoom->getItemId(),
                ]);
            } else {
                return $this->redirectToRoute('app_room_listall', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    private function getAvailableTemplates($type)
    {
        $templates = [];

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $currentUserItem = $legacyEnvironment->getCurrentUserItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomManager->setContextLimit($legacyEnvironment->getCurrentPortalItem()->getItemID());
        $roomManager->setTemplateLimit();
        $roomManager->select();

        $templateList = $roomManager->get();
        if ($templateList->isNotEmpty()) {
            $template = $templateList->getFirst();
            while ($template) {
                $availability = $template->getTemplateAvailability(); // $type === 'project'
                if ($type === 'community') {
                    $availability = $template->getCommunityTemplateAvailability();
                }

                $add = false;

                // free for all?
                if (!$add && $availability == '0') {
                    $add = true;
                }

                // only in community rooms
                if (!$add && $legacyEnvironment->inCommunityRoom() && $availability == '3') {
                    $add = true;
                }

                // same as above, but from portal context
                if (!$add && $legacyEnvironment->inPortal() && $availability == '3') {
                    // check if user is member in one of the templates community rooms
                    $communityList = $template->getCommunityList();
                    if ($communityList->isNotEmpty()) {
                        $userCommunityList = $currentUserItem->getRelatedCommunityList();
                        if ($userCommunityList->isNotEmpty()) {
                            $communityItem = $communityList->getFirst();
                            while ($communityItem) {
                                $userCommunityItem = $userCommunityList->getFirst();
                                while ($userCommunityItem) {
                                    if ($userCommunityItem->getItemID() == $communityItem->getItemID()) {
                                        $add = true;
                                        break;
                                    }

                                    $userCommunityItem = $userCommunityList->getNext();
                                }

                                $communityItem = $communityList->getNext();
                            }
                        }
                    }
                }

                // only for members
                if (!$add && $availability == '1' && $template->mayEnter($currentUserItem)) {
                    $add = true;
                }

                // only mods
                if (!$add && $availability == '2' && $template->mayEnter($currentUserItem)) {
                    if ($template->isModeratorByUserID($currentUserItem->getUserID(), $currentUserItem->getAuthSource())) {
                        $add = true;
                    }
                }

                if ($type != $template->getItemType()) {
                    $add = false;
                }

                if ($add) {
                    $label = $template->getTitle() . ' (ID: ' . $template->getItemID() . ')';
                    $templates[$label] = $template->getItemID();
                }

                $template = $templateList->getNext();
            }
        }

        return $templates;
    }

    private function memberStatus($roomItem)
    {
        $status = 'closed';
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        $roomService = $this->get('commsy_legacy.room_service');
        $item = $roomService->getRoomItem($roomItem->getItemId());

        if ($item) {
            $relatedUserArray = $currentUser->getRelatedUserList()->to_array();
            $roomUser = null;
            foreach ($relatedUserArray as $relatedUser) {
                if ($relatedUser->getContextId() == $item->getItemId()) {
                    $roomUser = $relatedUser;
                }
            }

            $mayEnter = false;
            if ($currentUser->isRoot()) {
                $mayEnter = true;
            } elseif (!empty($roomUser)) {
                $mayEnter = $item->mayEnter($roomUser);
            } else {
                // in case of the guest user, $roomUser is null
                if ($currentUser->isReallyGuest()) {
                    $mayEnter = $item->mayEnter($currentUser);
                }
            }

            if ($mayEnter) {
                if ($item->isOpen()) {
                    $status = 'enter';
                } else {
                    $status = 'join';
                }
            } elseif ($item->isLocked()) {
                $status = 'locked';
            } elseif (!empty($roomUser) and $roomUser->isRequested()) {
                $status = 'requested';
            } elseif (!empty($roomUser) and $roomUser->isRejected()) {
                $status = 'rejected';
            } else {
                if ($currentUser->isReallyGuest()) {
                    return 'forbidden';
                }
            }
        } else {

            $legacyEnvironment->activateArchiveMode();

            $item = $roomService->getRoomItem($roomItem->getItemId());
            $status = 'archived';

            $currentUser = $legacyEnvironment->getCurrentUserItem();
            $relatedUserArray = $currentUser->getRelatedUserList()->to_array();

            foreach ($relatedUserArray as $relatedUser) {
                if ($relatedUser->getContextId() == $item->getItemId()) {
                    $roomUser = $relatedUser;
                }
            }
            if ($currentUser->isRoot() || (!empty($roomUser) && $item->mayEnter($roomUser))) {
                $status = 'enter_archived';
            }

            $legacyEnvironment->deactivateArchiveMode();
        }
        return $status;
    }

    private function copySettings($masterRoom, $targetRoom)
    {
        $old_room = $masterRoom;
        $new_room = $targetRoom;

        $old_room_id = $old_room->getItemID();

        $environment = $this->get('commsy_legacy.environment')->getEnvironment();

        /**/
        $user_manager = $environment->getUserManager();
        $creator_item = $user_manager->getItem($new_room->getCreatorID());
        if ($creator_item->getContextID() == $new_room->getItemID()) {
            $creator_id = $creator_item->getItemID();
        } else {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($new_room->getItemID());
            $user_manager->setUserIDLimit($creator_item->getUserID());
            $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
            $user_manager->setModeratorLimit();
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
                $creator_item = $user_list->getFirst();
                $creator_id = $creator_item->getItemID();
            } else {
                throw new \Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->setPublishMaterialWantMail('yes');
        $creator_item->save();

        // copy room settings
        require_once('include/inc_room_copy_config.php');

        // save new room
        $new_room->save();

        // copy data
        require_once('include/inc_room_copy_data.php');
        /**/

        $targetRoom = $new_room;

        return $targetRoom;
    }
}
