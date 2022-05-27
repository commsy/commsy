<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Utils\ItemService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RoomAllController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', portalId)")
 */
class RoomAllController extends AbstractController
{
    /**
     * @Route("/portal/{portalId}/room/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     * @param Portal $portal
     * @param ItemService $itemService
     * @param RoomService $roomService
     * @param UserService $userService
     * @param LegacyEnvironment $environment
     * @param LegacyMarkup $legacyMarkup
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Portal $portal,
        ItemService $itemService,
        RoomService $roomService,
        UserService $userService,
        LegacyEnvironment $environment,
        LegacyMarkup $legacyMarkup,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);
        if($roomItem === null){
            return $this->redirectToRoute('app_dashboard_overview', [
                'roomId' => $itemId,
            ]);
        }

        $currentUser = $legacyEnvironment->getCurrentUser();

        $infoArray = $this->getDetailInfo($roomItem, $itemService, $legacyEnvironment);

        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);

        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);
        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        return [
            'item' => $roomItem,
            'currentUser' => $currentUser,
            'modifierList' => $infoArray['modifierList'],
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'memberStatus' => $memberStatus,
            'contactModeratorItems' => $contactModeratorItems,
            'portalId' => $portal->getId(),
        ];
    }

    private function getDetailInfo(
        $room,
        ItemService $itemService,
        cs_environment $legacyEnvironment
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
//        $userIds = [];
//        foreach ($userList as $user) {
//            $userIds[] = $user->getItemID();
//        }
//
//        $readerManager->getLatestReaderByUserIDArray($userIds, $room->getItemID());
//        foreach ($userList as $currentUser) {
//            $currentReader = $readerManager->getLatestReaderForUserByID($room->getItemID(), $currentUser->getItemID());
//            if ( !empty($currentReader) ) {
//                if ($currentReader['read_date'] >= $room->getModificationDate()) {
//                    $readSinceModificationCount++;
//                }
//
//                $readerCount++;
//            }
//        }

        $info['readCount'] = $readerCount;
        $info['readSinceModificationCount'] = $readSinceModificationCount;

        return $info;
    }
}