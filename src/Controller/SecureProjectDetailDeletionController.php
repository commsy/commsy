<?php

namespace App\Controller;

use App\Form\Type\Room\SecureDeleteType;
use App\Utils\RoomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SecureProjectDetailDeletionController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class SecureProjectDetailDeletionController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/settings/securedelete/{subRoomId}")
     * @Template
     * @Security("is_granted('ROOM_MODERATOR', subRoomId) or is_granted('PARENT_ROOM_MODERATOR', subRoomId)")
     */
    public function deleteOrLock(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        $subRoomId
    )
    {
        $roomItem = $roomService->getRoomItem($subRoomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $subRoomId);
        }

        $isProjectRoom = $roomItem->isProjectRoom();
        $communityRooms = $roomService->getCommunityRoomsForRoom($roomItem);
        $communityRoomIds = $roomService->getIdsForRooms($communityRooms);
        $projectRoomIsViewedFromItsCommunityRoom = ($isProjectRoom && in_array($roomId, $communityRoomIds));
        $cancelRoute = ($projectRoomIsViewedFromItsCommunityRoom) ? 'app_project_detail' : 'app_room_detail' ;
        $successRoute = ($projectRoomIsViewedFromItsCommunityRoom) ? 'app_project_list' : 'app_room_listall' ;

        $relatedGroupRooms = [];
        if ($isProjectRoom) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $deleteForm = $this->createForm(SecureDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $lockForm = $this->createForm(SecureDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('lock', [], 'profile')
        ]);

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $clickedButton = $deleteForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            if ($buttonName === 'cancel') {
                return $this->redirectToRoute($cancelRoute, [
                    'roomId' => $roomId,
                    'itemId' => $subRoomId,
                ]);
            } elseif ($buttonName === 'delete') {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to hosting context/room
                return $this->redirectToRoute($successRoute, [
                    'roomId' => $roomId,
                ]);
            } else {
                $deleteForm->clearErrors(true);
            }
        }

        $lockForm->handleRequest($request);

        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            $clickedButton = $lockForm->getClickedButton();
            $buttonName = $clickedButton ? $clickedButton->getName() : '';

            if ($buttonName === 'cancel') {
                return $this->redirectToRoute($cancelRoute, [
                    'roomId' => $roomId,
                    'itemId' => $subRoomId,
                ]);
            } elseif ($buttonName === 'lock') {
                $roomItem->lock();
                $roomItem->save();

                // redirect back to hosting context/room
                return $this->redirectToRoute($successRoute, [
                    'roomId' => $roomId,
                ]);
            } else {
                $lockForm->clearErrors(true);
            }
        }

        return [
            'delete_form' => $deleteForm->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ];

    }
}