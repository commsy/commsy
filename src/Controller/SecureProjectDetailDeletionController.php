<?php

namespace App\Controller;

use App\Form\Type\Room\DeleteType;
use App\Form\Type\Room\SecureDeleteType;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SecureProjectDetailDeletionController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class SecureProjectDetailDeletionController extends AbstractController
{

    /**
     * @Route("/room/{roomId}/settings/securedelete/{projectId}")
     * @Template
     * @Security("is_granted('ROOM_MODERATOR', projectId) or is_granted('PARENT_ROOM_MODERATOR', projectId)")
     */
    public function deleteOrLock(
        $roomId,
        Request $request,
        RoomService $roomService,
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        $projectId
    )
    {
        $roomItem = $roomService->getRoomItem($projectId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $projectId);
        }

        $relatedGroupRooms = [];
        if ($roomItem instanceof \cs_project_item) {
            $relatedGroupRooms = $roomItem->getGroupRoomList()->to_array();
        }

        $form = $this->createForm(SecureDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('delete', [], 'profile')
        ]);

        $lockForm = $this->createForm(SecureDeleteType::class, $roomItem, [
            'confirm_string' => $translator->trans('lock', [], 'profile')
        ]);

        $form->handleRequest($request);
        if($form->get('cancel')->isClicked()){
            return $this->redirectToRoute('app_project_list', [
                'roomId' => $roomId,
            ]);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $roomItem->delete();
                $roomItem->save();

                // redirect back to project ws list
                return $this->redirectToRoute('app_project_list', [
                    'roomId' => $roomId,
                ]);
            }else{
                $form->clearErrors(true);
            }
        }

        $lockForm->handleRequest($request);
        if ($lockForm->isSubmitted() && $lockForm->isValid()) {
            if ($lockForm->get('lock')->isClicked()) {
                $roomItem->setStatus(3);
                $roomItem->save();

                // redirect back to project ws list
                return $this->redirectToRoute('app_project_list', [
                    'roomId' => $roomId,
                ]);
            }else{
                $lockForm->clearErrors(true);
            }
        }

        if ($lockForm->get('lock')->isClicked()) {
            $form = $this->createForm(DeleteType::class, $roomItem, [
                'confirm_string' => $translator->trans('delete', [], 'profile')
            ]);
        }elseif($form->get('delete')->isClicked()){
            $lockForm = $this->createForm(DeleteType::class, $roomItem, [
                'confirm_string' => $translator->trans('lock', [], 'profile')
            ]);
        }

        return [
            'form' => $form->createView(),
            'relatedGroupRooms' => $relatedGroupRooms,
            'lock_form' => $lockForm->createView(),
        ];

    }
}