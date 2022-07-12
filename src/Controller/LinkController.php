<?php

namespace App\Controller;

use App\Services\LegacyEnvironment;
use App\Utils\GroupService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\RoomService;
use FeedIo\Feed\Item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class LinkController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class LinkController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/link/{itemId}/{rubric}")
     * @Template()
     * @param GroupService $groupService
     * @param ItemService $itemService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function showAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);

        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }
        
        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = array();
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = array();
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return array(
            'linkedItemsByRubric' => $returnArray
        );
    }


    /**
     * @Route("/room/{roomId}/material/link/{itemId}")
     * @Template()
     * @param GroupService $groupService
     * @param ItemService $itemService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function showDetailAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);

        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }
        
        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = array();
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = array();
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return array(
            'linkedItemsByRubric' => $returnArray
        );
    }

    /**
     * @Route("/room/{roomId}/material/link/{itemId}")
     * @Template()
     * @param GroupService $groupService
     * @param ItemService $itemService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function showDetailPrintAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);

        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }
        
        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = array();
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = array();
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return array(
            'linkedItemsByRubric' => $returnArray
        );
    }


    /**
     * @Route("/room/{roomId}/material/link/{itemId}")
     * @Template()
     * @param GroupService $groupService
     * @param ItemService $itemService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function showDetailShortAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);

        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
            }
        }
        $ids = $item->getAllLinkeditemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }
        
        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = array();
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = array();
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return array(
            'linkedItemsByRubric' => $returnArray
        );
    }


    /**
     * @Route("/room/{roomId}/material/link/{itemId}")
     * @Template()
     * @param GroupService $groupService
     * @param ItemService $itemService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function showDetailLongAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId)
    {
        $item = $itemService->getItem($itemId);

        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
            }
        }
        $ids = $item->getAllLinkedItemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = array();
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = array();
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return array(
            'linkedItemsByRubric' => $returnArray
        );
    }

    /**
     * @Route("/room/{roomId}/material/link/{itemId}")
     * @Template()
     * @param GroupService $groupService
     * @param ItemService $itemService
     * @param LabelService $labelService
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function showDetailLongToggleAction(
        GroupService $groupService,
        ItemService $itemService,
        LabelService $labelService,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId
    ) {
        $item = $itemService->getItem($itemId);

        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $group = $groupService->getGroup($tempLabel->getItemID());
                $membersList = $group->getMemberItemList();
                $linkedItems = $membersList->to_array();
            }
        }
        $ids = $item->getAllLinkedItemIDArray();
        foreach ($ids as $id) {
            $linkedItems[] = $itemService->getItem($id);
        }

        usort($linkedItems, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });

        $environment = $legacyEnvironment->getEnvironment();
        $linkedFullItems = array();
        foreach ($linkedItems as $linkedItem) {
            $manager = $environment->getManager($linkedItem->getItemType());
            $item = $manager->getItem($linkedItem->getItemId());
            if ($item->getItemType() == 'user') {
                $item->setTitle($item->getFullName());
            }
            $linkedFullItems[] = $item;
        }

        $linkedFullItemsSortedByRubric = array();
        foreach ($linkedFullItems as $linkedFullItem) {
            $linkedFullItemsSortedByRubric[$linkedFullItem->getItemType()][] = $linkedFullItem;
        }

        $rubrics = $roomService->getRubricInformation($roomId);

        $returnArray = array();
        foreach ($rubrics as $rubric) {
            if (isset($linkedFullItemsSortedByRubric[$rubric])) {
                $returnArray[$rubric] = $linkedFullItemsSortedByRubric[$rubric];
            }
        }

        return array(
            'linkedItemsByRubric' => $returnArray
        );
    }
}
