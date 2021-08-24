<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use App\Entity\ZzzRoom;
use App\Event\UserJoinedRoomEvent;
use App\Filter\HomeFilterType;
use App\Filter\RoomFilterType;
use App\Form\Type\ContextType;
use App\Form\Type\ModerationSupportType;
use App\Repository\PortalRepository;
use App\Repository\UserRepository;
use App\Room\Copy\LegacyCopy;
use App\RoomFeed\RoomFeedGenerator;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\RoomCategoriesService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_user_item;
use DateTimeImmutable;
use Exception;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdater;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RoomController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class RoomController extends AbstractController
{
    /**
     * @Route("/room/{roomId}", requirements={
     *     "roomId": "\d+"
     * })
     * @Template()
     * @param Request $request
     * @param ItemService $itemService
     * @param RoomService $roomService
     * @param RoomFeedGenerator $roomFeedGenerator
     * @param LegacyMarkup $legacyMarkup
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @return array
     */
    public function homeAction(
        Request $request,
        ItemService $itemService,
        RoomService $roomService,
        RoomFeedGenerator $roomFeedGenerator,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $legacyEnvironment,
        ParameterBagInterface $parameterBag,
        int $roomId
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        // get room item
        $roomItem = $roomService->getRoomItem($roomId);

        // fall back on default theme if rooms theme is not supported anymore
        if ($roomItem && !in_array($roomItem->getColorArray()['schema'], $parameterBag->get('liip_theme.themes'))) {
            $roomItem->setColorArray(array('schema' => 'default'));
            $roomItem->save();
        }

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

        $header = "latest entries";

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in feed generator
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

        // support mail
        $serviceContact = [
            'show' => false,
        ];
        $portalItem = $legacyEnvironment->getCurrentPortalItem();
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
            $homeInformationEntry = $itemService->getTypedItem($entryId);

            // This check is now present in settings form. Check also added here to secure display of rooms with old and invalid settings in database.
            if (!in_array($homeInformationEntry->getItemType(), [CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_MATERIAL_TYPE, CS_TODO_TYPE])) {
                $roomItem->setwithInformationBox(false);
                $homeInformationEntry = null;
            } else {
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
     * @param Request $request
     * @param ReaderService $readerService
     * @param RoomFeedGenerator $roomFeedGenerator
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $max
     * @return array
     */
    public function feedAction(
        Request $request,
        ReaderService $readerService,
        RoomFeedGenerator $roomFeedGenerator,
        LegacyEnvironment $environment,
        RoomService $roomService,
        int $roomId,
        int $max = 10
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        // get room item for information panel
        $roomItem = $roomService->getRoomItem($roomId);

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
        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

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
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array|JsonResponse
     */
    public function moderationsupportAction(
        Request $request,
        TranslatorInterface $translator,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $moderationsupportData = array();
        $form = $this->createForm(ModerationSupportType::class, $moderationsupportData, array(
            'action' => $this->generateUrl('app_room_moderationsupport', array(
                'roomId' => $roomId,
            ))
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $legacyEnvironment = $environment->getEnvironment();

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
     * @param Request $request [description]
     * @param RoomService $roomService
     * @param FilterBuilderUpdater $filterBuilderUpdater
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array [type]           [description]
     */
    public function listAllAction(
        Request $request,
        RoomService $roomService,
        FilterBuilderUpdater $filterBuilderUpdater,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $showRooms = $portalItem->getShowRoomsOnHome();
        switch ($showRooms) {
            case 'onlyprojectrooms':
                $roomTypes = [CS_PROJECT_TYPE];
                break;
            case 'onlycommunityrooms':
                $roomTypes = [CS_COMMUNITY_TYPE];
                break;
            default:
                $roomTypes = [CS_PROJECT_TYPE, CS_COMMUNITY_TYPE];
                break;
        }

        $filterForm = $this->createForm(RoomFilterType::class, null, [
            'showTime' => $portalItem->showTime(),
            'timePulses' => $roomService->getTimePulses(),
            'timePulsesDisplayName' => ucfirst($portalItem->getCurrentTimeName()),
        ]);

        $filterForm->handleRequest($request);

        $count = 0;
        $countAll = 0;

        // ***** Active rooms *****
        $repository = $this->getDoctrine()->getRepository(Room::class);
        $activeRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId(), $roomTypes);
        $activeRoomQueryBuilder->select($activeRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll += $activeRoomQueryBuilder->getQuery()->getSingleScalarResult();

        // filtered rooms
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filterBuilderUpdater->addFilterConditions($filterForm, $activeRoomQueryBuilder);
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
        $archivedRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portalItem->getItemId(), $roomTypes);
        $archivedRoomQueryBuilder->select($archivedRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll += $archivedRoomQueryBuilder->getQuery()->getSingleScalarResult();

        if ($request->query->has('room_filter')) {
            $roomFilter = $request->query->get('room_filter');

            // "archived" not set or archived != 1 = include archived rooms in list 
            if (!isset($roomFilter['archived']) || $roomFilter['archived'] != "1") {
                if ($filterForm->isSubmitted() && $filterForm->isValid()) {
                    $filterBuilderUpdater->addFilterConditions($filterForm, $archivedRoomQueryBuilder);
                    $count += $archivedRoomQueryBuilder->getQuery()->getSingleScalarResult();
                }
            }
        }
        // archived rooms have to be included if they aren't explicitely excluded!
        else {
            $filterBuilderUpdater->addFilterConditions($filterForm, $archivedRoomQueryBuilder);
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
     * @param Request $request
     * @param RoomService $roomService
     * @param FilterBuilderUpdater $filterBuilderUpdater
     * @param LegacyEnvironment $environment
     * @param UserRepository $userRepository
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @return array
     */
    public function feedAllAction(
        Request $request,
        RoomService $roomService,
        FilterBuilderUpdater $filterBuilderUpdater,
        LegacyEnvironment $environment,
        UserRepository $userRepository,
        PortalRepository $portalRepository,
        int $roomId,
        string $sort='activity',
        int $max = 10,
        int $start = 0
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $portal = $portalRepository->find($legacyEnvironment->getCurrentPortalID());


        $showRooms = $portal->getShowRoomsOnHome();
        switch ($showRooms) {
            case 'onlyprojectrooms':
                $roomTypes = [CS_PROJECT_TYPE];
                break;
            case 'onlycommunityrooms':
                $roomTypes = [CS_COMMUNITY_TYPE];
                break;
            default:
                $roomTypes = [CS_PROJECT_TYPE, CS_COMMUNITY_TYPE];
                break;
        }

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $roomFilter = $request->get('roomFilter');
        if (!$roomFilter) {
            $roomFilter = $request->query->get('room_filter');
        }

        // ***** Active rooms *****
        $repository = $this->getDoctrine()->getRepository('App:Room');
        $activeRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portal->getId(), $roomTypes, $sort);
        $activeRoomQueryBuilder->setMaxResults($max);
        $activeRoomQueryBuilder->setFirstResult($start);



        if ($roomFilter) {
            $filterForm = $this->createForm(RoomFilterType::class, $roomFilter, [
                'showTime' => $portal->getShowTimePulses(),
                'timePulses' => $roomService->getTimePulses(),
                'timePulsesDisplayName' => ucfirst($portal->getTimePulseName($legacyEnvironment->getSelectedLanguage())),
            ]);

            // manually bind values from the request
            $filterForm->submit($roomFilter);

            $filterBuilderUpdater->addFilterConditions($filterForm, $activeRoomQueryBuilder);
        }

        $rooms = $activeRoomQueryBuilder->getQuery()->getResult();

        // ***** Archived rooms *****
        if(!$roomFilter || !isset($roomFilter['archived']) || $roomFilter['archived'] != "1") {
            $legacyEnvironment->activateArchiveMode();
            $repository = $this->getDoctrine()->getRepository('App:ZzzRoom');
            $archivedRoomQueryBuilder = $repository->getMainRoomQueryBuilder($portal->getId(), $roomTypes, $sort);
            $archivedRoomQueryBuilder->setMaxResults($max);
            $archivedRoomQueryBuilder->setFirstResult($start);

            if ($roomFilter) {
                $filterForm = $this->createForm(RoomFilterType::class, $roomFilter, [
                    'showTime' => $portal->getShowTimePulses(),
                    'timePulses' => $roomService->getTimePulses(),
                    'timePulsesDisplayName' => ucfirst($portal->getTimePulseName($legacyEnvironment->getSelectedLanguage())),
                ]);
                $filterForm->submit($roomFilter);
                $filterBuilderUpdater->addFilterConditions($filterForm, $archivedRoomQueryBuilder);
            }
            $rooms = array_merge($rooms, $archivedRoomQueryBuilder->getQuery()->getResult());
        }



        if ($legacyEnvironment->isArchiveMode()) {
            $legacyEnvironment->deactivateArchiveMode();
        }

        $projectsMemberStatus = array();
        foreach ($rooms as $room) {

            try{
                $projectsMemberStatus[$room->getItemId()] = $this->memberStatus($room, $legacyEnvironment, $roomService);
                $contactUsers = $userRepository->getContactsByRoomId($room->getItemId());
                $moderators = $userRepository->getModeratorsByRoomId($room->getItemId());

                if(empty($contactUsers)){
                    $contactUsers = array_unique(array_merge($contactUsers, $moderators), SORT_REGULAR);
                }

                $contactsString = "";
                $iDsString = "";

                foreach($contactUsers as $contactUser){
                    $contactsString .= $contactUser->getFullName();
                    $iDsString .= $contactUser->getItemID();
                    $contactsString .= ", ";
                    $iDsString .= ",";
                }

                $contactsString = rtrim($contactsString, ", ");
                $iDsString = rtrim($iDsString, ", ");
                if(strlen($iDsString) > 1 and strlen($contactsString) > 1){
                    $room->setContactPersons($contactsString . ";" . $iDsString);
                }
            }catch (Exception $e){
                // do nothing
            }
        }
        return [
            'roomId' => $roomId,
            'portal' => $portal,
            'rooms' => $rooms,
            'projectsMemberStatus' => $projectsMemberStatus,
        ];
    }

    /**
     * @Route("/room/{roomId}/all/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     * @param ItemService $itemService
     * @param RoomService $roomService
     * @param UserService $userService
     * @param LegacyEnvironment $environment
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        ItemService $itemService,
        RoomService $roomService,
        UserService $userService,
        LegacyEnvironment $environment,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        $currentUser = $legacyEnvironment->getCurrentUser();

        $infoArray = $this->getDetailInfo($roomItem, $itemService, $legacyEnvironment);

        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);

        $showRoomModerationActions = false;
        /** @var cs_user_item $roomUser */
        $roomUser = $currentUser->getRelatedUserItemInContext($itemId);
        if ($currentUser->isRoot() || (isset($roomUser) && $roomUser->isModerator())) {
            $showRoomModerationActions = true;
        } else {
            $portalUser = $currentUser->getRelatedPortalUserItem();
            if ($portalUser && $portalUser->isModerator()) {
                $showRoomModerationActions = true;
            }
        }

        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);
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

    private function getDetailInfo(
        $room,
        ItemService $itemService,
        \cs_environment $legacyEnvironment
    ) {
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
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Template()
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $itemId
     * @return array|string|RedirectResponse
     */
    public function requestAction(
        Request $request,
        LegacyEnvironment $environment,
        UserService $userService,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

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

                // TODO: try to make use of UserService->cloneUser() instead

                $currentUserItem = $legacyEnvironment->getCurrentUserItem();
                $privateRoomUserItem = $currentUserItem->getRelatedPrivateRoomUserItem();

                $sourceUser = $privateRoomUserItem ?? $currentUserItem;
                $newUser = $sourceUser->cloneData();

                $newUser->setContextID($roomItem->getItemID());

                $userService->cloneUserPicture($sourceUser, $newUser);

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
                    $userService->addUserToSystemGroupAll($newUser, $roomItem);
                }

                if ($roomItem->getAGBStatus()) {
                    $newUser->setAGBAcceptanceDate(new DateTimeImmutable());
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
     * @Route("/room/{roomId}/all/create", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @param Request $request
     * @param RoomService $roomService
     * @param RoomCategoriesService $roomCategoriesService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array|RedirectResponse
     * @throws Exception
     * @Security("is_granted('ITEM_EDIT', 'NEW')")
     */
    public function createAction(
        Request $request,
        RoomService $roomService,
        UserService $userService,
        RoomCategoriesService $roomCategoriesService,
        LegacyEnvironment $environment,
        EventDispatcherInterface $eventDispatcher,
        CalendarsService $calendarsService,
        LegacyCopy $legacyCopy,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();

        $type = '';
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

        $timesDisplay = ucfirst($currentPortalItem->getCurrentTimeName());
        $times = $roomService->getTimePulses(true);

        $current_user = $legacyEnvironment->getCurrentUserItem();
        $communityManager = $legacyEnvironment->getCommunityManager();
        $communityManager->setContextLimit($currentPortalItem->getItemID());
        $communityManager->select();
        $community_list = $communityManager->get();
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

        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($currentPortalItem->getItemId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }

        $linkRoomCategoriesMandatory = $currentPortalItem->isTagMandatory() && count($roomCategories) > 0;

        if(!isset($type)){
            $type = 'project'; //TODO: what is supposed to happen here? Initial, type is null - with this, the next method errors
        }

        $translator = $legacyEnvironment->getTranslationObject();
        $msg = $translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');

        $templates = $roomService->getAvailableTemplates($type);

        // necessary, since the data field malfunctions when added via listener call (#2979)
        $templates['*'.$msg] = '-1';

        // re-sort array by elements
        foreach($templates as $index => $entry){
            if(!($index == 'No template')){
                unset($templates[$index]);
                $templates[$index] = $entry;
            }
        }

        uasort($templates,  function($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

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

                // user room-related options will only be set in project workspaces
                if (isset($context['type_sub']['createUserRooms'])) {
                    $legacyRoom->setShouldCreateUserRooms($context['type_sub']['createUserRooms']);
                }
                if (isset($context['type_sub']['userroom_template'])) {
                    $userroomTemplate = $roomService->getRoomItem($context['type_sub']['userroom_template']);
                    if ($userroomTemplate) {
                        $legacyRoom->setUserRoomTemplateID($userroomTemplate->getItemID());
                    }
                }

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

                $calendarsService->createCalendar($legacyRoom, null, null, true);

                // take values from a template?
                if (isset($context['type_sub']['master_template'])) {
                    $masterRoom = $roomService->getRoomItem($context['type_sub']['master_template']);
                    if ($masterRoom) {
                        $legacyRoom = $this->copySettings($masterRoom, $legacyRoom, $legacyEnvironment, $legacyCopy);
                    }
                }

                // NOTE: we can only set the language after copying settings from any room template, otherwise the language
                // would get overwritten by the room template's language setting
                $legacyRoom->setLanguage($context['language']);
                $legacyRoom->save();

                $legacyRoomUsers = $userService->getListUsers($legacyRoom->getItemID(), null, null, true);
                foreach ($legacyRoomUsers as $user) {
                    $event = new UserJoinedRoomEvent($user, $legacyRoom);
                    $eventDispatcher->dispatch($event);
                }

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

    private function memberStatus(
        $roomItem,
        \cs_environment $legacyEnvironment,
        RoomService $roomService
    ) {
        $status = 'closed';
        $currentUser = $legacyEnvironment->getCurrentUserItem();
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

    private function copySettings($masterRoom, $targetRoom, \cs_environment $legacyEnvironment, LegacyCopy $legacyCopy)
    {
        $old_room = $masterRoom;
        $new_room = $targetRoom;

        /**/
        $user_manager = $legacyEnvironment->getUserManager();
        $creator_item = $user_manager->getItem($new_room->getCreatorID());
        if ($creator_item->getContextID() != $new_room->getItemID()) {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($new_room->getItemID());
            $user_manager->setUserIDLimit($creator_item->getUserID());
            $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
            $user_manager->setModeratorLimit();
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
                $creator_item = $user_list->getFirst();
            } else {
                throw new Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->setPublishMaterialWantMail('yes');
        $creator_item->save();

        // copy room settings
        $legacyCopy->copySettings($old_room, $new_room);

        // save new room
        $new_room->save();

        // copy data
        $legacyCopy->copyData($old_room, $new_room, $creator_item);

        /**/

        return $new_room;
    }
}
