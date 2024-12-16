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

use App\Filter\ProjectFilterType;
use App\Form\Type\Room\DeleteType;
use App\Room\Copy\LegacyCopy;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Utils\ItemService;
use App\Utils\ProjectService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ProjectController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class ProjectController extends AbstractController
{
    public function __construct(private readonly ReaderService $readerService)
    {
    }

    #[Route(path: '/room/{roomId}/project/feed/{start}/{sort}')]
    public function feed(
        Request $request,
        ProjectService $projectService,
        ReaderService $readerService,
        LegacyEnvironment $environment,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date_rev'
    ): Response
    {
        $legacyEnvironment = $environment->getEnvironment();

        // setup filter form
        $defaultFilterValues = ['activated' => true];
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, ['action' => $this->generateUrl('app_project_list', ['roomId' => $roomId])]);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        // get material list from manager service
        $projects = $projectService->getListProjects($roomId, $max, $start, $sort);
        $projectsMemberStatus = [];
        foreach ($projects as $project) {
            $projectsMemberStatus[$project->getItemId()] = $this->memberStatus($project, $legacyEnvironment);
        }

        $readerList = [];
        foreach ($projects as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getStatusForItem($item)->value;
        }

        $currentUser = $legacyEnvironment->getCurrentUser();

        return $this->render('project/feed.html.twig', ['roomId' => $roomId, 'projects' => $projects, 'projectsMemberStatus' => $projectsMemberStatus, 'readerList' => $readerList, 'currentUser' => $currentUser]);
    }

    #[Route(path: '/room/{roomId}/project')]
    public function list(
        Request $request,
        ProjectService $projectService,
        LegacyEnvironment $environment,
        int $roomId
    ): Response
    {
        // setup filter form
        $defaultFilterValues = ['activated' => true];
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, ['action' => $this->generateUrl('app_project_list', ['roomId' => $roomId])]);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $projectService->getCountArray($roomId);

        $usageInfo = false;

        $legacyEnvironment = $environment->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('project')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('project');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('project');
        }

        return $this->render('project/list.html.twig', ['roomId' => $roomId, 'form' => $filterForm, 'module' => 'project', 'itemsCountArray' => $itemsCountArray, 'usageInfo' => $usageInfo, 'userCanCreateContext' => $legacyEnvironment->getCurrentUserItem()->isAllowedToCreateContext()]);
    }

    #[Route(path: '/room/{roomId}/project/{itemId}', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function detail(
        ItemService $itemService,
        RoomService $roomService,
        UserService $userService,
        LegacyMarkup $legacyMarkup,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ): Response
    {
        $legacyEnvironment = $environment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        $currentUser = $legacyEnvironment->getCurrentUser();
        $infoArray = $this->getDetailInfo($itemId, $environment, $itemService);
        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);
        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);

        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        return $this->render('project/detail.html.twig', [
            'roomId' => $roomId,
            'item' => $roomItem,
            'currentUser' => $currentUser,
            'modifierList' => $infoArray['modifierList'],
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'memberStatus' => $memberStatus,
            'contactModeratorItems' => $contactModeratorItems,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/project/create', requirements: ['itemId' => '\d+'])]
    public function create(
        int $roomId
    ): Response {
        return $this->render('project/create.html.twig', [
            'roomId' => $roomId,
        ]);
    }

    #[Route(path: '/room/{roomId}/project/{itemId}/edit', requirements: ['itemId' => '\d+'])]
    public function edit()
    {
    }

    #[Route(path: '/room/{roomId}/project/{itemId}/delete', requirements: ['itemId' => '\d+'])]
    public function delete(
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        int $roomId,
        int $itemId
    ): Response {
        $roomItem = $roomService->getRoomItem($itemId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$itemId);
        }

        $form = $this->createForm(DeleteType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('delete', [], 'profile'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $roomItem->delete();
            $roomItem->save();

            return $this->redirectToRoute('app_project_list', ['roomId' => $roomId]);
        }

        return $this->render('project/delete.html.twig', [
            'form' => $form,
        ]);
    }

    private function getDetailInfo(
        int $roomId,
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService
    ) {
        $info = [];

        // modifier
        $room = $itemService->getItem($roomId);
        $info['modifierList'][$roomId] = $itemService->getAdditionalEditorsForItem($room);

        $readCountDescription = $this->readerService->getReadCountDescriptionForItem($room);
        $info['userCount'] = $readCountDescription->getUserTotal();
        $info['readCount'] = $readCountDescription->getReadTotal();
        $info['readSinceModificationCount'] = $readCountDescription->getReadSinceModification();

        return $info;
    }

    private function copySettings($masterRoom, $targetRoom, LegacyCopy $legacyCopy, cs_environment $legacyEnvironment)
    {
        $user_manager = $legacyEnvironment->getUserManager();
        $creator_item = $user_manager->getItem($targetRoom->getCreatorID());
        if ($creator_item->getContextID() != $targetRoom->getItemID()) {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($targetRoom->getItemID());
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
        $creator_item->save();

        // copy room settings
        $legacyCopy->copySettings($masterRoom, $targetRoom);

        // save new room
        $targetRoom->save();

        // copy data
        $legacyCopy->copyData($masterRoom, $targetRoom, $creator_item);

        return $targetRoom;
    }

    private function memberStatus($item, cs_environment $legacyEnvironment)
    {
        $status = 'closed';
        $currentUser = $legacyEnvironment->getCurrentUserItem();

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

        return $status;
    }
}
