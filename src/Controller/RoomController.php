<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Event\UserJoinedRoomEvent;
use App\Filter\HomeFilterType;
use App\Filter\RoomFilterType;
use App\Form\Type\ContextType;
use App\Hash\HashManager;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
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
use cs_environment;
use cs_user_item;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdater;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use UnexpectedValueException;

/**
 * Class RoomController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class RoomController extends AbstractController
{
    #[Route(path: '/room/{roomId}', requirements: ['roomId' => '\d+'])]
    public function home(
        Request $request,
        ItemService $itemService,
        RoomService $roomService,
        RoomFeedGenerator $roomFeedGenerator,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $legacyEnvironment,
        ThemeRepositoryInterface $themeRepository,
        UserRepository $userRepository,
        HashManager $hashManager,
        int $roomId
    ): Response {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        // get room item
        $roomItem = $roomService->getRoomItem($roomId);

        // fall back on default theme if rooms theme is not supported anymore
        if ($roomItem) {
            $themeName = 'commsy/'.$roomItem->getColorArray()['schema'];
            if ('commsy/default' !== $themeName && !$themeRepository->findOneByName($themeName)) {
                $roomItem->setColorArray(['schema' => 'default']);
                $roomItem->save();
            }
        }

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createForm(HomeFilterType::class, null, ['action' => $this->generateUrl('app_room_home', ['roomId' => $roomId]), 'hasHashtags' => $roomItem->withBuzzwords(), 'hasCategories' => $roomItem->withTags()]);

        $header = 'latest entries';

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in feed generator
            $roomFeedGenerator->setFilterConditions($filterForm);
            $header = 'search results';
        }

        // ...and prepare some data
        $timeSpread = $roomItem->getTimeSpread();
        $numNewEntries = $roomItem->getNewEntries($timeSpread);
        $pageImpressions = $roomItem->getPageImpressions($timeSpread);

        $numActiveMember = $roomItem->getActiveMembers($timeSpread);
        $numTotalMember = $roomItem->getAllUsers();

        $moderators = [];
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
        if ($roomItem->getBGImageFilename()) {
            $backgroundImage = $this->generateUrl('getBackground', ['roomId' => $roomId, 'imageType' => 'custom']);
        } else {
            $backgroundImage = $this->generateUrl('getBackground', ['roomId' => $roomId, 'imageType' => 'theme']);
        }

        $logoImage = null;
        if ($roomItem->getLogoFilename()) {
            $logoImage = $this->generateUrl('getLogo', ['roomId' => $roomId]);
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

        // RSS-Feed / iCal
        $rss = [
            'show' => false,
            'url' => $this->generateUrl('app_rss', [
                'contextId' => $roomId,
            ]),
        ];

        if (!$roomItem->isLocked() && !$roomItem->isClosed()) {
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();

            if ($roomItem->isRSSOn()) {
                $rss['show'] = true;
            }

            if (!$roomItem->isOpenForGuests()) {
                if ($currentUserItem->isUser()) {
                    $hash = $hashManager->getUserHashes($currentUserItem->getItemID());

                    $rss['url'] = $this->generateUrl('app_rss', [
                        'contextId' => $roomId,
                        'hid' => $hash->getRss(),
                    ]);
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

        $userTasks = $userRepository->getConfirmableUserByContextId($roomId)->getQuery()->getResult();

        $pinnedItems = $itemService->getPinnedItems($roomId);

        return $this->render('room/home.html.twig', [
            'homeInformationEntry' => $homeInformationEntry,
            'form' => $filterForm,
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
            'header' => $header,
            'isModerator' => $legacyEnvironment->getCurrentUserItem()->isModerator(),
            'userTasks' => $userTasks,
            'deletesRoomIfUnused' => $portalItem->isActivatedDeletingUnusedRooms(),
            'daysUnusedBeforeRoomDeletion' => $portalItem->getDaysUnusedBeforeDeletingRooms(),
            'pinnedItemsCount' => count($pinnedItems)
        ]);
    }

    #[Route(path: '/room/{roomId}/feed/{start}/{sort}', requirements: ['roomId' => '\d+'])]
    public function feed(
        Request $request,
        ReaderService $readerService,
        RoomFeedGenerator $roomFeedGenerator,
        LegacyEnvironment $environment,
        RoomService $roomService,
        int $roomId,
        int $max = 10
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        // get room item for information panel
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $filterForm = $this->createForm(HomeFilterType::class, null, ['action' => $this->generateUrl('app_room_home', ['roomId' => $roomId]), 'hasHashtags' => $roomItem->withBuzzwords(), 'hasCategories' => $roomItem->withTags()]);

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

        $readerList = [];
        foreach ($feedList as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        return $this->render('room/list.html.twig', ['feedList' => $feedList, 'readerList' => $readerList, 'showRating' => $current_context->isAssessmentActive()]);
    }

    /**
     * @param Request $request [description]
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    #[Route(path: '/room/{roomId}/all', requirements: ['roomId' => '\d+'])]
    public function listAll(
        Request $request,
        RoomService $roomService,
        FilterBuilderUpdater $filterBuilderUpdater,
        LegacyEnvironment $environment,
        PortalRepository $portalRepository,
        RoomRepository $roomRepository,
        int $roomId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $portal = $portalRepository->find($legacyEnvironment->getCurrentPortalID());

        $showRooms = $portal->getShowRoomsOnHome();
        $roomTypes = match ($showRooms) {
            'onlyprojectrooms' => [CS_PROJECT_TYPE],
            'onlycommunityrooms' => [CS_COMMUNITY_TYPE],
            default => [CS_PROJECT_TYPE, CS_COMMUNITY_TYPE],
        };

        $sort = $request->getSession()->get('sortRooms', $portal->getSortRoomsBy() ?? 'activity');

        $filterForm = $this->createForm(RoomFilterType::class, [
            'template' => $portal->getDefaultFilterHideTemplates(),
            'archived' => $portal->getDefaultFilterHideArchived(),
        ], [
            'showTime' => $portal->getShowTimePulses(),
            'timePulses' => $roomService->getTimePulses(),
            'timePulsesDisplayName' => ucfirst((string) $portal->getTimePulseName($legacyEnvironment->getSelectedLanguage())),
        ]);

        $filterForm->handleRequest($request);

        // Get both query builder - for active and archived workspaces
        $activeRoomQueryBuilder = $roomRepository->getMainRoomQueryBuilder($portal->getId(), $roomTypes);

        // Get the sum of all active and archived workspaces before applying any filters
        $activeRoomQueryBuilder->select($activeRoomQueryBuilder->expr()->count('r.itemId'));
        $countAll = $activeRoomQueryBuilder->getQuery()->getSingleScalarResult();

        // Get the sum of all filtered workspaces after filtering
        $filterBuilderUpdater->addFilterConditions($filterForm, $activeRoomQueryBuilder);
        $count = $activeRoomQueryBuilder->getQuery()->getSingleScalarResult();

        $userMayCreateContext = false;
        $currentUser = $legacyEnvironment->getCurrentUser();
        if (!$currentUser->isRoot()) {
            $portalUser = $currentUser->getRelatedPortalUserItem();

            if ($portalUser) {
                if ($portalUser->isModerator()) {
                    $userMayCreateContext = true;
                } elseif ('all' == $portal->getCommunityRoomCreationStatus() || 'portal' == $portal->getProjectRoomCreationStatus()) {
                    $userMayCreateContext = $currentUser->isAllowedToCreateContext();
                }
            }
        } else {
            $userMayCreateContext = true;
        }

        return $this->render('room/list_all.html.twig', [
            'roomId' => $roomId,
            'portal' => $portal,
            'form' => $filterForm,
            'itemsCountArray' => [
                'count' => $count,
                'countAll' => $countAll,
            ],
            'userMayCreateContext' => $userMayCreateContext,
            'sort' => $sort,
        ]);
    }

    #[Route(path: '/room/{roomId}/all/feed/{start}/{sort}')]
    public function feedAll(
        Request $request,
        RoomService $roomService,
        FilterBuilderUpdater $filterBuilderUpdater,
        LegacyEnvironment $environment,
        UserRepository $userRepository,
        PortalRepository $portalRepository,
        RoomRepository $roomRepository,
        int $roomId,
        string $sort = '',
        int $max = 10,
        int $start = 0
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $portal = $portalRepository->find($legacyEnvironment->getCurrentPortalID());

        $showRooms = $portal->getShowRoomsOnHome();
        $roomTypes = match ($showRooms) {
            'onlyprojectrooms' => [CS_PROJECT_TYPE],
            'onlycommunityrooms' => [CS_COMMUNITY_TYPE],
            default => [CS_PROJECT_TYPE, CS_COMMUNITY_TYPE],
        };

        if (empty($sort)) {
            $sort = $request->getSession()->get('sortRooms', $portal->getSortRoomsBy() ?? 'activity');
        }
        $request->getSession()->set('sortRooms', $sort);

        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $roomFilter = $request->attributes->get('roomFilter') ?: $request->query->all('room_filter');

        // Prepare query builder for active and archived rooms
        $activeRoomQueryBuilder = $roomRepository->getMainRoomQueryBuilder($portal->getId(), $roomTypes, $sort);

        $activeRoomQueryBuilder->setMaxResults($max);
        $activeRoomQueryBuilder->setFirstResult($start);

        $filterForm = $this->createForm(RoomFilterType::class, [
            'template' => $portal->getDefaultFilterHideTemplates(),
            'archived' => $portal->getDefaultFilterHideArchived(),
        ], [
            'showTime' => $portal->getShowTimePulses(),
            'timePulses' => $roomService->getTimePulses(),
            'timePulsesDisplayName' => ucfirst((string) $portal->getTimePulseName($legacyEnvironment->getSelectedLanguage())),
        ]);

        // manually bind values from the request
        if ($roomFilter) {
            $filterForm->submit($roomFilter);
        }

        // apply filter
        $filterBuilderUpdater->addFilterConditions($filterForm, $activeRoomQueryBuilder);

        $rooms = $activeRoomQueryBuilder->getQuery()->getResult();

        $projectsMemberStatus = [];
        foreach ($rooms as $room) {
            try {
                $projectsMemberStatus[$room->getItemId()] = $this->memberStatus($room, $legacyEnvironment,
                    $roomService);
                $contactUsers = $userRepository->getContactsByRoomId($room->getItemId());
                $moderators = $userRepository->getModeratorsByRoomId($room->getItemId());

                if (empty($contactUsers)) {
                    $contactUsers = array_unique(array_merge($contactUsers, $moderators), SORT_REGULAR);
                }

                $contactsString = implode(', ', array_map(static fn (User $user) => $user->getFullName(), $contactUsers));

                $iDsString = implode(',', array_map(static fn (User $user) => $user->getItemID(), $contactUsers));

                if (strlen($iDsString) > 1 && strlen($contactsString) > 1) {
                    $room->setContactPersons($contactsString.';'.$iDsString);
                }
            } catch (Exception) {
                // do nothing
            }
        }

        return $this->render('room/feed_all.html.twig', [
            'roomId' => $roomId,
            'portal' => $portal,
            'rooms' => $rooms,
            'projectsMemberStatus' => $projectsMemberStatus,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/all/create', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_NEW')]
    public function create(
        Request $request,
        RoomService $roomService,
        UserService $userService,
        RoomCategoriesService $roomCategoriesService,
        LegacyEnvironment $environment,
        EventDispatcherInterface $eventDispatcher,
        CalendarsService $calendarsService,
        LegacyCopy $legacyCopy,
        int $roomId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();

        $type = '';
        $context = $request->get('context');
        if ($context) {
            $type = $context['type_select'] ?? '';
        }

        // NOTE: `getDefault...TemplateID()` may also return '-1' (if no default template is defined)
        $defaultId = '-1';
        if ('project' === $type) {
            $defaultId = $currentPortalItem->getDefaultProjectTemplateID();
        } elseif ('community' === $type) {
            $defaultId = $currentPortalItem->getDefaultCommunityTemplateID();
        }
        $defaultTemplateIDs = ('-1' === $defaultId) ? [] : [$defaultId];

        $timesDisplay = ucfirst((string) $currentPortalItem->getCurrentTimeName());
        $times = $roomService->getTimePulses(true);

        $current_user = $legacyEnvironment->getCurrentUserItem();
        $portalUser = $current_user->getRelatedPortalUserItem();
        $types = [];
        if ($portalUser->isModerator()) {
            $types = ['project' => 'project', 'community' => 'community'];
        } else {
            $roomItem = $roomService->getRoomItem($roomId);

            if ('portal' == $currentPortalItem->getProjectRoomCreationStatus()) {
                $types['project'] = 'project';
            } elseif (CS_COMMUNITY_TYPE == $roomItem->getType()) {
                $types['project'] = 'project';
            }

            if ('all' == $currentPortalItem->getCommunityRoomCreationStatus()) {
                $types['community'] = 'community';
            }
        }

        $linkCommunitiesMandantory = true;
        if ('optional' == $currentPortalItem->getProjectRoomLinkStatus()) {
            $linkCommunitiesMandantory = false;
        }

        $roomCategories = [];
        foreach ($roomCategoriesService->getListRoomCategories($currentPortalItem->getItemId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }

        $linkRoomCategoriesMandatory = $currentPortalItem->isTagMandatory() && count($roomCategories) > 0;

        if (!isset($type)) {
            $type = 'project'; // TODO: what is supposed to happen here? Initial, type is null - with this, the next method errors
        }

        $translator = $legacyEnvironment->getTranslationObject();
        $msg = $translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');

        $templates = $roomService->getAvailableTemplates($type);

        // necessary, since the data field malfunctions when added via listener call (#2979)
        $templates['*'.$msg] = '-1';

        // re-sort array by elements
        uasort($templates, fn ($a, $b) => $a <=> $b);

        $formData = [];
        $form = $this->createForm(ContextType::class, $formData, [
            'types' => $types,
            'templates' => $templates,
            'preferredChoices' => $defaultTemplateIDs,
            'timesDisplay' => $timesDisplay,
            'times' => $times,
            'linkCommunitiesMandantory' => $linkCommunitiesMandantory,
            'roomCategories' => $roomCategories,
            'linkRoomCategoriesMandatory' => $linkRoomCategoriesMandatory,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            if ($form->get('save')->isClicked()) {
                if ('project' == $formData['type_select']) {
                    $roomManager = $legacyEnvironment->getProjectManager();
                } elseif ('community' == $formData['type_select']) {
                    $roomManager = $legacyEnvironment->getCommunityManager();
                } else {
                    throw new UnexpectedValueException('Error Processing Request: Unrecognized room type', 1);
                }

                $legacyRoom = $roomManager->getNewItem();

                $currentUser = $legacyEnvironment->getCurrentUserItem();
                $legacyRoom->setCreatorItem($currentUser);
                $legacyRoom->setCreationDate(getCurrentDateTimeInMySQL());
                $legacyRoom->setModificatorItem($currentUser);
                $legacyRoom->setContextID($legacyEnvironment->getCurrentPortalID());
                $legacyRoom->open();

                if ('project' == $formData['type_select'] && isset($context['type_sub']['community_rooms'])) {
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

                $timeIntervals = $context['type_sub']['time_interval'] ?? [];
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
                return $this->redirectToRoute('app_roomall_detail', [
                    'portalId' => $legacyEnvironment->getCurrentPortalID(),
                    'itemId' => $legacyRoom->getItemId(),
                ]);
            }

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_room_listall', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return $this->render('room/create.html.twig', [
            'form' => $form,
        ]);
    }

    private function memberStatus(
        $roomItem,
        cs_environment $legacyEnvironment,
        RoomService $roomService
    ) {
        $currentUser = $legacyEnvironment->getCurrentUserItem();
        $item = $roomService->getRoomItem($roomItem->getItemId());

        if ($item) {
            $relatedUserArray = $currentUser->getRelatedUserList()->to_array();
            $filteredUserArray = array_filter($relatedUserArray, fn (cs_user_item $user) => $user->getContextId() == $item->getItemId());
            $roomUser = array_values($filteredUserArray)[0] ?? null;

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

            if ($item->getArchived()) {
                if ($item->isLocked()) {
                    return 'locked_archived';
                } elseif (!empty($roomUser) && $mayEnter) {
                    return 'enter_archived';
                } else {
                    return 'archived';
                }
            } elseif ($mayEnter) {
                if ($item->isOpen()) {
                    return 'enter';
                } else {
                    return 'join';
                }
            } elseif ($item->isLocked()) {
                return 'locked';
            } elseif (!empty($roomUser) and $roomUser->isRequested()) {
                return 'requested';
            } elseif (!empty($roomUser) and $roomUser->isRejected()) {
                return 'rejected';
            } else {
                if ($currentUser->isReallyGuest()) {
                    return 'forbidden';
                }
            }
        }

        return 'closed';
    }

    private function copySettings($masterRoom, $targetRoom, cs_environment $legacyEnvironment, LegacyCopy $legacyCopy)
    {
        $old_room = $masterRoom;
        $new_room = $targetRoom;

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
            if ($user_list->isNotEmpty() and 1 == $user_list->getCount()) {
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

        return $new_room;
    }
}
