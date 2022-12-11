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

use App\Services\LegacyEnvironment;
use App\Utils\GroupService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\RoomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LinkController.
 */
#[Security("is_granted('ITEM_ENTER', roomId)")]
class LinkController extends AbstractController
{
    #[Route(path: '/room/{roomId}/link/{itemId}/{rubric}')]
    public function showAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ): \Symfony\Component\HttpFoundation\Response {
        $item = $itemService->getItem($itemId);

        $linkedItems = [];
        if ('label' == $item->getItemType()) {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ('group' == $tempLabel->getLabelType()) {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, fn ($firstItem, $secondItem) => $firstItem->getModificationDate() < $secondItem->getModificationDate());

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = [];
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ('user' == $item->getItemType()) {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = [];
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = [];
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return $this->render('link/show.html.twig', ['linkedItemsByRubric' => $returnArray]);
    }

    #[Route(path: '/room/{roomId}/material/link/{itemId}')]
    public function showDetailAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ): \Symfony\Component\HttpFoundation\Response {
        $item = $itemService->getItem($itemId);

        $linkedItems = [];
        if ('label' == $item->getItemType()) {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ('group' == $tempLabel->getLabelType()) {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, fn ($firstItem, $secondItem) => $firstItem->getModificationDate() < $secondItem->getModificationDate());

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = [];
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ('user' == $item->getItemType()) {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = [];
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = [];
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return $this->render('link/show_detail.html.twig', ['linkedItemsByRubric' => $returnArray]);
    }

    #[Route(path: '/room/{roomId}/material/link/{itemId}')]
    public function showDetailPrintAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ): \Symfony\Component\HttpFoundation\Response {
        $item = $itemService->getItem($itemId);

        $linkedItems = [];
        if ('label' == $item->getItemType()) {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ('group' == $tempLabel->getLabelType()) {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, fn ($firstItem, $secondItem) => $firstItem->getModificationDate() < $secondItem->getModificationDate());

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = [];
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ('user' == $item->getItemType()) {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = [];
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = [];
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return $this->render('link/show_detail_print.html.twig', ['linkedItemsByRubric' => $returnArray]);
    }

    #[Route(path: '/room/{roomId}/material/link/{itemId}')]
    public function showDetailShortAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ): \Symfony\Component\HttpFoundation\Response {
        $item = $itemService->getItem($itemId);

        $linkedItems = [];
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, fn ($firstItem, $secondItem) => $firstItem->getModificationDate() < $secondItem->getModificationDate());

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = [];
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ('user' == $item->getItemType()) {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = [];
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = [];
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return $this->render('link/show_detail_short.html.twig', ['linkedItemsByRubric' => $returnArray]);
    }

    #[Route(path: '/room/{roomId}/material/link/{itemId}')]
    public function showDetailLongAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId): \Symfony\Component\HttpFoundation\Response
    {
        $item = $itemService->getItem($itemId);

        $linkedItems = [];
        $ids = $item->getAllLinkedItemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, fn ($firstItem, $secondItem) => $firstItem->getModificationDate() < $secondItem->getModificationDate());

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = [];
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ('user' == $item->getItemType()) {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = [];
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = [];
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return $this->render('link/show_detail_long.html.twig', ['linkedItemsByRubric' => $returnArray]);
    }

    #[Route(path: '/room/{roomId}/material/link/{itemId}')]
    public function showDetailLongToggleAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ): \Symfony\Component\HttpFoundation\Response {
        $item = $itemService->getItem($itemId);

        $linkedItems = [];
        if ('label' == $item->getItemType()) {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ('group' == $tempLabel->getLabelType()) {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkedItemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, fn ($firstItem, $secondItem) => $firstItem->getModificationDate() < $secondItem->getModificationDate());

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = [];
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ('user' == $item->getItemType()) {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = [];
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = [];
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return $this->render('link/show_detail_long_toggle.html.twig', ['linkedItemsByRubric' => $returnArray]);
    }
}
