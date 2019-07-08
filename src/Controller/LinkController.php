<?php

namespace App\Controller;

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
     */
    public function showAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $labelService = $this->get('commsy_legacy.label_service');
        
        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy_legacy.group_service');
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
        
        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
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

        $roomService = $this->get('commsy_legacy.room_service');
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
     */
    public function showDetailAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $labelService = $this->get('commsy_legacy.label_service');
        
        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy_legacy.group_service');
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
        
        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
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

        $roomService = $this->get('commsy_legacy.room_service');
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
     */
    public function showDetailPrintAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $labelService = $this->get('commsy_legacy.label_service');
        
        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy.group_service');
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
        
        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
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

        $roomService = $this->get('commsy_legacy.room_service');
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
     */
    public function showDetailShortAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $labelService = $this->get('commsy_legacy.label_service');
        
        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy_legacy.group_service');
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
        
        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
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

        $roomService = $this->get('commsy_legacy.room_service');
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
     */
    public function showDetailLongAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $labelService = $this->get('commsy_legacy.label_service');
        
        $linkedItems = array();
        if ($item->getItemType() == 'label') {
            $tempLabel = $labelService->getLabel($item->getItemId());
            if ($tempLabel->getLabelType() == 'group') {
                $groupService = $this->get('commsy_legacy.group_service');
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
        
        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
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

        $roomService = $this->get('commsy_legacy.room_service');
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
