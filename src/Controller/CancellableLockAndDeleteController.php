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

use App\Form\Type\Room\CancellableDeleteType;
use App\Form\Type\Room\CancellableLockType;
use App\Utils\RoomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CancellableLockAndDeleteController.
 */
#[Security("(is_granted('ROOM_MODERATOR', itemId) or is_granted('PARENT_ROOM_MODERATOR', itemId)) and is_granted('ITEM_DELETE', itemId)")]
class CancellableLockAndDeleteController extends AbstractController
{
    #[Route(path: '/room/{roomId}/settings/cancellabledelete/{itemId}')]
    #[Security("(is_granted('ROOM_MODERATOR', itemId) or is_granted('PARENT_ROOM_MODERATOR', itemId)) and is_granted('ITEM_DELETE', itemId)")]
    public function deleteOrLock(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        $itemId
    ): Response {
        $roomItem = $roomService->getRoomItem($itemId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$itemId);
        }

        $isGroupRoom = $roomItem->isGroupRoom();

        $isProjectRoom = $roomItem->isProjectRoom();
        $communityRooms = $isProjectRoom ? $roomService->getCommunityRoomsForRoom($roomItem) : [];
        $communityRoomIds = $roomService->getIdsForRooms($communityRooms);
        $projectRoomIsViewedFromItsCommunityRoom = ($isProjectRoom && in_array($roomId, $communityRoomIds));

        $listRoute = $isGroupRoom ? 'app_group_list' : ($projectRoomIsViewedFromItsCommunityRoom ? 'app_project_list' : 'app_room_listall');

        $relatedGroupRooms = [];
        if ($isProjectRoom) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $deleteForm = $this->createForm(CancellableDeleteType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('delete', [], 'profile'),
        ]);

        $lockForm = $this->createForm(CancellableLockType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('lock', [], 'profile'),
        ]);

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $clickedButton = $deleteForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            if ('cancel' === $buttonName) {
                return $this->render('cancellable_lock_and_delete/delete_or_lock.html.twig');
            } elseif ('delete' === $buttonName) {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to hosting context/room/group
                return $this->redirectToRoute($listRoute, [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }
        }

        $lockForm->handleRequest($request);

        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            $clickedButton = $lockForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            if ('cancel' === $buttonName) {
                return $this->render('cancellable_lock_and_delete/delete_or_lock.html.twig');
            } elseif ('lock' === $buttonName) {
                $roomItem->lock();
                $roomItem->save();

                return $this->render('cancellable_lock_and_delete/delete_or_lock.html.twig');
            }
        }

        return $this->render('cancellable_lock_and_delete/delete_or_lock.html.twig', [
            'delete_form' => $deleteForm->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ]);
    }

    #[Route(path: '/room/{roomId}/settings/unlock/{itemId}')]
    #[Security("is_granted('ROOM_MODERATOR', itemId) or is_granted('PARENT_ROOM_MODERATOR', itemId)")]
    public function unlock(
        $roomId,
        RoomService $roomService,
        $itemId
    ): Response {
        $roomItem = $roomService->getRoomItem($itemId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id '.$itemId);
        }

        $isGroupRoom = $roomItem->isGroupRoom();
        $group = $isGroupRoom ? $roomItem->getLinkedGroupItem() : null;
        $groupId = $group ? $group->getItemID() : null;

        $isProjectRoom = $roomItem->isProjectRoom();
        $communityRooms = $isProjectRoom ? $roomService->getCommunityRoomsForRoom($roomItem) : [];
        $communityRoomIds = $roomService->getIdsForRooms($communityRooms);
        $projectRoomIsViewedFromItsCommunityRoom = ($isProjectRoom && in_array($roomId, $communityRoomIds));
        $detailRoute = $isGroupRoom ? 'app_group_detail' : ($projectRoomIsViewedFromItsCommunityRoom ? 'app_project_detail' : 'app_roomall_detail');

        $roomItem->unlock();
        $roomItem->save();

        if ('app_roomall_detail' === $detailRoute) {
            return $this->redirectToRoute($detailRoute, [
                'portalId' => $roomItem->getContextID(),
                'itemId' => $isGroupRoom ? $groupId : $itemId,
            ]);
        } else {
            return $this->redirectToRoute($detailRoute, [
                'roomId' => $roomId,
                'itemId' => $isGroupRoom ? $groupId : $itemId,
            ]);
        }
    }
}
