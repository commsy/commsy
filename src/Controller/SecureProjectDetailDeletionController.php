<?php

namespace App\Controller;

use App\Form\Type\Room\DeleteType;
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

        $relatedGroupRooms = [];
        if ($roomItem instanceof \cs_project_item) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $deleteForm = $this->createForm(SecureDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $lockForm = $this->createForm(SecureDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('lock', [], 'profile')
        ]);

        $deleteForm->handleRequest($request);
        if ($deleteForm->get('cancel')->isClicked()) {
            return $this->redirectToRoute('app_project_list', [
                'roomId' => $roomId,
            ]);
        }
        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            if ($deleteForm->get('delete')->isClicked()) {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to project ws list
                return $this->redirectToRoute('app_project_list', [
                    'roomId' => $roomId,
                ]);
            } else {
                $deleteForm->clearErrors(true);
            }
        }

        $lockForm->handleRequest($request);
        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            if ($lockForm->get('lock')->isClicked()) {
                $roomItem->lock();
                $roomItem->save();

                // redirect back to project ws list
                return $this->redirectToRoute('app_project_list', [
                    'roomId' => $roomId,
                ]);
            } else {
                $lockForm->clearErrors(true);
            }
        }

        if ($lockForm->get('lock')->isClicked()) {
            $deleteForm = $this->createForm(DeleteType::class, $roomItem, [
                'confirm_string' => $translator->trans('delete', [], 'profile')
            ]);
        } elseif ($deleteForm->get('delete')->isClicked()) {
            $lockForm = $this->createForm(DeleteType::class, $roomItem, [
                'confirm_string' => $translator->trans('lock', [], 'profile')
            ]);
        }

        return [
            'delete_form' => $deleteForm->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ];

    }
}