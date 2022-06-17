<?php

namespace App\Controller;

use App\Form\Type\Room\CancellableDeleteType;
use App\Form\Type\Room\CancellableLockType;
use App\Utils\RoomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CancellableLockAndDeleteController
 * @package App\Controller
 * @Security("(is_granted('ROOM_MODERATOR', itemId) or is_granted('PARENT_ROOM_MODERATOR', itemId)) and is_granted('ITEM_DELETE', itemId)")
 */
class CancellableLockAndDeleteController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/settings/cancellabledelete/{itemId}")
     * @Template
     * @Security("(is_granted('ROOM_MODERATOR', itemId) or is_granted('PARENT_ROOM_MODERATOR', itemId)) and is_granted('ITEM_DELETE', itemId)")
     */
    public function deleteOrLock(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        $itemId
    ) {
        $roomItem = $roomService->getRoomItem($itemId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $itemId);
        }

        $isGroupRoom = $roomItem->isGroupRoom();
        $group = $isGroupRoom ? $roomItem->getLinkedGroupItem() : null;
        $groupId = $group ? $group->getItemID() : null;

        $isProjectRoom = $roomItem->isProjectRoom();
        $communityRooms = $isProjectRoom ? $roomService->getCommunityRoomsForRoom($roomItem) : [];
        $communityRoomIds = $roomService->getIdsForRooms($communityRooms);
        $projectRoomIsViewedFromItsCommunityRoom = ($isProjectRoom && in_array($roomId, $communityRoomIds));

        $detailRoute = $isGroupRoom ? 'app_group_detail' : ($projectRoomIsViewedFromItsCommunityRoom ? 'app_project_detail' : 'app_roomall_detail') ;
        $listRoute = $isGroupRoom ? 'app_group_detail' : ($projectRoomIsViewedFromItsCommunityRoom ? 'app_project_list' : 'app_room_listall') ;

        if ($detailRoute === 'app_roomall_detail') {
            $detailRedirectResponse = $this->redirectToRoute($detailRoute, [
                'portalId' => $roomItem->getContextID(),
                'itemId' => $isGroupRoom ? $groupId : $itemId,
            ]);
        } else {
            $detailRedirectResponse = $this->redirectToRoute($detailRoute, [
                'roomId' => $roomId,
                'itemId' => $isGroupRoom ? $groupId : $itemId,
            ]);
        }

        $relatedGroupRooms = [];
        if ($isProjectRoom) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $deleteForm = $this->createForm(CancellableDeleteType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $lockForm = $this->createForm(CancellableLockType::class, [], [
            'room' => $roomItem,
            'confirm_string' => $translator->trans('lock', [], 'profile')
        ]);

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $clickedButton = $deleteForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            if ($buttonName === 'cancel') {
                return $detailRedirectResponse;
            } elseif ($buttonName === 'delete') {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to hosting context/room/group
                return $this->redirectToRoute($listRoute, [
                    'roomId' => $roomId,
                    'itemId' => $isGroupRoom ? $groupId : $itemId,
                ]);
            }
        }

        $lockForm->handleRequest($request);

        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            $clickedButton = $lockForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            if ($buttonName === 'cancel') {
                return $detailRedirectResponse;
            } elseif ($buttonName === 'lock') {
                $roomItem->lock();
                $roomItem->save();

                return $detailRedirectResponse;
            }
        }

        return [
            'delete_form' => $deleteForm->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/settings/unlock/{itemId}")
     * @Template
     * @Security("is_granted('ROOM_MODERATOR', itemId) or is_granted('PARENT_ROOM_MODERATOR', itemId)")
     */
    public function unlock(
        $roomId,
        Request $request,
        RoomService $roomService,
        $itemId
    ) {
        $roomItem = $roomService->getRoomItem($itemId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $itemId);
        }

        $isGroupRoom = $roomItem->isGroupRoom();
        $group = $isGroupRoom ? $roomItem->getLinkedGroupItem() : null;
        $groupId = $group ? $group->getItemID() : null;

        $isProjectRoom = $roomItem->isProjectRoom();
        $communityRooms = $isProjectRoom ? $roomService->getCommunityRoomsForRoom($roomItem) : [];
        $communityRoomIds = $roomService->getIdsForRooms($communityRooms);
        $projectRoomIsViewedFromItsCommunityRoom = ($isProjectRoom && in_array($roomId, $communityRoomIds));
        $detailRoute = $isGroupRoom ? 'app_group_detail' : ($projectRoomIsViewedFromItsCommunityRoom ? 'app_project_detail' : 'app_roomall_detail') ;

        $roomItem->unlock();
        $roomItem->save();

        if ($detailRoute === 'app_roomall_detail') {
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
