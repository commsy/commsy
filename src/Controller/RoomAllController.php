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

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Utils\ItemService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RoomAllController.
 */
#[Security("is_granted('ITEM_ENTER', portalId)")]
class RoomAllController extends AbstractController
{
    #[Route(path: '/portal/{portalId}/room/{itemId}', requirements: ['itemId' => '\d+'])]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function detailAction(
        Portal $portal,
        ItemService $itemService,
        RoomService $roomService,
        UserService $userService,
        LegacyEnvironment $environment,
        LegacyMarkup $legacyMarkup,
        int $itemId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $currentUser = $legacyEnvironment->getCurrentUser();

        $infoArray = $this->getDetailInfo($roomItem, $itemService, $legacyEnvironment);

        $memberStatus = $userService->getMemberStatus($roomItem, $currentUser);

        $contactModeratorItems = $roomService->getContactModeratorItems($itemId);
        $legacyMarkup->addFiles($itemService->getItemFileList($itemId));

        return $this->render('room_all/detail.html.twig', [
            'item' => $roomItem,
            'currentUser' => $currentUser,
            'modifierList' => $infoArray['modifierList'],
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'memberStatus' => $memberStatus,
            'contactModeratorItems' => $contactModeratorItems,
            'portalId' => $portal->getId(),
        ]);
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

        $info['readCount'] = $readerCount;
        $info['readSinceModificationCount'] = $readSinceModificationCount;

        return $info;
    }
}
