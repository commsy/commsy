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

use App\Form\Type\MyViewsType;
use App\Hash\HashManager;
use App\Model\SearchData;
use App\Repository\CalendarsRepository;
use App\Repository\PortalRepository;
use App\Repository\SavedSearchRepository;
use App\Repository\ServerRepository;
use App\RoomFeed\RoomFeedGenerator;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DashboardController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class DashboardController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/dashboard/{roomId}')]
    public function overview(
         ItemService $itemService,
         LegacyEnvironment $environment,
         PortalRepository $portalRepository,
         ServerRepository $serverRepository,
         CalendarsRepository $calendarsRepository,
         HashManager $hashManager,
         int $roomId
     ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getPrivateRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $portal = $portalRepository->find($legacyEnvironment->getCurrentPortalID());
        $server = $serverRepository->getServer();

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

                $hash = $hashManager->getUserHashes($currentUserItem->getItemID());

                $iCal['aboUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $hash->getIcal(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $iCal['exportUrl'] = $this->generateUrl('app_ical_getcontent', [
                    'contextId' => $roomId,
                    'hid' => $hash->getIcal(),
                    'export' => true,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $user = $legacyEnvironment->getCurrentUserItem();
        $userList = $user->getRelatedUserList()->to_array();
        $contextIds = [];
        foreach ($userList as $user) {
            $contextIds[] = $user->getContextId();
        }

        $calendars = $calendarsRepository->findBy(['context_id' => $contextIds, 'external_url' => ['', null]]);

        $contextArray = [];
        foreach ($calendars as $calendar) {
            $roomItemCalendar = $itemService->getTypedItem($calendar->getContextId());
            if ($roomItemCalendar) {
                $contextArray[$calendar->getContextId()][] = $roomItemCalendar->getTitle();
            }
        }

        // given the current portal configuration, is the current user allowed to create new rooms?
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

        return $this->render('dashboard/overview.html.twig', [
            'roomItem' => $roomItem,
            'dashboardLayout' => $roomItem->getDashboardLayout(),
            'iCal' => $iCal,
            'calendars' => $calendars,
            'contextArray' => $contextArray,
            'portal' => $portal,
            'server' => $server,
            'userMayCreateContext' => $userMayCreateContext,
        ]);
    }

    #[Route(path: '/dashboard/{roomId}/feed/{start}/{sort}')]
    public function feed(
        int $roomId,
        Request $request,
        ReaderService $readerService,
        RoomFeedGenerator $roomFeedGenerator,
        LegacyEnvironment $legacyEnvironment,
        int $max = 10
    ): Response {
        $lastId = $request->query->getInt('lastId');
        $lastId = $lastId === 0 ? null : $lastId;

        $environment = $legacyEnvironment->getEnvironment();

        $feedList = $roomFeedGenerator->getDashboardFeedList($max, $lastId);
        $user = $environment->getPortalUserItem();

        $readerList = [];
        $feedItems = [];
        foreach ($feedList as $item) {
            if (null != $item) {
                $feedItems[] = $item;
                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                $readerList[$item->getItemId()] = $readerService->getChangeStatusForUserByID($item->getItemId(), $relatedUser->getItemId());
            }
        }

        return $this->render('dashboard/feed.html.twig', [
            'feedList' => $feedItems,
            'readerList' => $readerList,
            'currentContextId' => $roomId,
        ]);
    }

    #[Route(path: '/dashboard/{roomId}/edit')]
    public function edit(
        Request $request,
        TranslatorInterface $translator,
        LegacyEnvironment $environment,
        int $roomId
    ): JsonResponse {
        $requestContent = json_decode($request->getContent(), null, 512, JSON_THROW_ON_ERROR);

        $legacyEnvironment = $environment->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getPrivateRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $roomItem->setDashboardLayout($requestContent->data);
        $roomItem->save();

        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->trans('dashboard changed', [], 'messages');

        return new JsonResponse(['message' => $message, 'timeout' => '5550', 'layout' => 'cs-notify-message', 'data' => []]);
    }

    #[Route(path: '/dashboard/{roomId}/myviews')]
    public function myViews(
        $roomId,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        SavedSearchRepository $repository
    ): Response
    {
        $searchData = new SearchData();

        // get the current user's saved searches
        $currentUser = $legacyEnvironment->getEnvironment()->getCurrentUserItem();
        $portalUserId = $currentUser->getRelatedPortalUserItem()->getItemId();

        $savedSearches = $repository->findByAccountId($portalUserId);
        $searchData->setSavedSearches($savedSearches);

        $myViewsForm = $this->createForm(MyViewsType::class, $searchData, [
            'action' => $this->generateUrl('app_dashboard_myviews', [
                'roomId' => $roomId,
            ]),
        ]);
        $myViewsForm->handleRequest($request);

        if ($myViewsForm->isSubmitted() && $myViewsForm->isValid()) {
            $savedSearch = $searchData->getSelectedSavedSearch();

            if ($savedSearch) {
                $savedSearchURL = $savedSearch->getSearchUrl();
                if ($savedSearchURL) {
                    // redirect to the search_url stored for the chosen saved search
                    return $this->redirect($request->getSchemeAndHttpHost() . $savedSearchURL);
                }
            } else {
                return $this->redirectToRoute('app_search_results', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return $this->render('dashboard/my_views.html.twig', [
            'myViewsForm' => $myViewsForm,
        ]);
    }

    #[Route(path: '/dashboard/{roomId}/externalaccess')]
    public function externalaccess(
        LegacyEnvironment $environment,
        int $roomId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $user = $legacyEnvironment->getCurrentUser()->getRelatedPortalUserItem();

        $itemManager = $legacyEnvironment->getItemManager();
        $releasedIds = $itemManager->getExternalViewerEntriesForRoom($roomId);
        $viewableIds = $itemManager->getExternalViewerEntriesForUser($user->getUserID());

        $releasedItems = [];
        foreach ($releasedIds as $releasedId) {
            $tempItem = $itemManager->getItem($releasedId);
            if ($tempItem) {
                $tempManager = $legacyEnvironment->getManager($tempItem->getItemType());
                $releasedItems[] = $tempManager->getItem($releasedId);
            }
        }

        $viewableItems = [];
        foreach ($viewableIds as $viewableId) {
            $tempItem = $itemManager->getItem($viewableId);
            if ($tempItem) {
                $tempManager = $legacyEnvironment->getManager($tempItem->getItemType());
                $viewableItems[] = $tempManager->getItem($viewableId);
            }
        }

        return $this->render('dashboard/externalaccess.html.twig', ['releaseItems' => $releasedItems, 'viewableItems' => $viewableItems]);
    }
}
