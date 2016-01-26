<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

class ItemController extends Controller
{
    /**
     * @Route("/room/{roomId}/item/{itemId}/editdescription")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editDescriptionAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');
        
        $formData = array();
        $tempItem = NULL;
        
        if ($item->getItemType() == 'material') {
            // get material from MaterialService
            $tempItem = $materialService->getMaterial($itemId);
            if (!$tempItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }
            $formData = $transformer->transform($tempItem);
        } else if ($item->getItemType() == 'section') {
            // get section from MaterialService
            $tempItem = $materialService->getSection($itemId);
            if (!$tempItem) {
                throw $this->createNotFoundException('No section found for id ' . $roomId);
            }
            $formData = $transformer->transform($tempItem);
        }
        
        $form = $this->createForm('itemDescription', $formData, array('itemId' => $itemId));
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $tempItem = $transformer->applyTransformation($tempItem, $form->getData());
                $tempItem->save();
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            
            return $this->redirectToRoute('commsy_item_savedescription', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'itemId' => $itemId,
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/savedescription")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveDescriptionAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        
        $tempItem = NULL;
        
        if ($item->getItemType() == 'material') {
            $tempItem = $materialService->getMaterial($itemId);
        } else if ($item->getItemType() == 'section') {
            $tempItem = $materialService->getSection($itemId);
        }

        $itemArray = array($tempItem);
    
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'modifierList' => $modifierList
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/editworkflow")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editWorkflowAction($roomId, $itemId, Request $request)
    {
        $roomService = $this->get('commsy.room_service');
        $room = $roomService->getRoomItem($roomId);

        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.item');
        
        $formData = array();
        $tempItem = NULL;
        
        if ($item->getItemType() == 'material') {
            // get material from MaterialService
            $tempItem = $materialService->getMaterial($itemId);
            if (!$tempItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }
            $formData = $transformer->transform($tempItem);
        }
        
        $form = $this->createForm('itemWorkflow', $formData, array());
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $tempItem = $transformer->applyTransformation($tempItem, $form->getData());
                $tempItem->save();
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            
            return $this->redirectToRoute('commsy_material_saveworkflow', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        $workflowData['textGreen'] = $room->getWorkflowTrafficLightTextGreen();
        $workflowData['textYellow'] = $room->getWorkflowTrafficLightTextYellow();
        $workflowData['textRed'] = $room->getWorkflowTrafficLightTextRed();

        return array(
            'item' => $tempItem,
            'form' => $form->createView(),
            'workflow' => $workflowData
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/editlinks/{feedAmount}", defaults={"feedAmount" = 20})
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editLinksAction($roomId, $itemId, $feedAmount, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy.room_service');
        
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $roomItem = $roomService->getRoomItem($roomId);
        
        $formData = array();
        $optionsData = array();
        
        // get all items that are linked or can be linked
        $rubricInformation = $roomService->getRubricInformation($roomId);
        $optionsData['filterRubric']['all'] = 'all';
        foreach ($rubricInformation as $rubric) {
            $optionsData['filterRubric'][$rubric] = $rubric;
        }

        $optionsData['filterPublic']['public'] = 'public';
        $optionsData['filterPublic']['all'] = 'all';

        $itemManager = $environment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);
        $itemManager->setTypeArrayLimit($rubricInformation);

        // get all linked items
        $itemLinkedList = $itemManager->getItemList($item->getAllLinkedItemIDArray());
        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $itemService->getTypedItem($tempLinkedItem->getItemId());
            if ($tempTypedLinkedItem->getItemType() != 'user') {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getTitle();
            } else {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getFullname();
            }
            $tempLinkedItem = $itemLinkedList->getNext();
        }
        if (empty($optionsData['itemsLinked'])) {
            $optionsData['itemsLinked'] = array();
        }
        // add number of linked items to feed amount
        $countLinked = count($optionsData['itemsLinked']);

        $itemManager->setIntervalLimit($feedAmount + $countLinked);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        // get all items except linked items
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem && !array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
            }
            $tempItem = $itemList->getNext();
            
        }

        $formData['itemsLinked'] = $item->getAllLinkedItemIDArray();

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($environment->getCurrentUser()->getUserId());
        $itemManager->setIntervalLimit(10);
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $latestItem = $latestItemList->getFirst();
        while ($latestItem) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && !array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) {
                $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = array();
        }
        
        // get all categories -> tree
        $categoryService = $this->get('commsy.category_service');
        $categories = $categoryService->getTags($roomId);
        $optionsData['categories'] = $this->getChoicesAsTree($categories);
        
        $categoriesList = $item->getTagList();
        $categoryItem = $categoriesList->getFirst();
        while ($categoryItem) {
            $formData['categories'][] = $categoryItem->getItemId();
            $categoryItem = $categoriesList->getNext();
        }

        // get all hashtags -> list
        $buzzwordManager = $environment->getBuzzwordManager();
        $buzzwordManager->setContextLimit($roomId);
        $buzzwordManager->select();
        $buzzwordList = $buzzwordManager->get();
        $buzzwordItem = $buzzwordList->getFirst();
        while ($buzzwordItem) {
            $optionsData['hashtags'][$buzzwordItem->getItemId()] = $buzzwordItem->getTitle();
            $selected_ids = $buzzwordItem->getAllLinkedItemIDArrayLabelVersion();
            if (in_array($itemId, $selected_ids)) {
                $formData['hashtags'][] = $buzzwordItem->getItemId();
            }
            $buzzwordItem = $buzzwordList->getNext();
        }
        
        $form = $this->createForm('itemLinks', $formData, array(
            'filterRubric' => $optionsData['filterRubric'],
            'filterPublic' => $optionsData['filterPublic'],
            'items' => $optionsData['items'],
            'itemsLinked' => $optionsData['itemsLinked'],
            'itemsLatest' => $optionsData['itemsLatest'],
            'categories' => $optionsData['categories'],
            'hashtags' => $optionsData['hashtags']
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            // if ($form->get('save')->isClicked()) {
                // ToDo ...
                $data = $form->getData();

                $itemData = array_merge($data['itemsLinked'], $data['itemsLatest']);

                // save linked entries
                $item->setLinkedItemsByIDArray($itemData);

                // save categories
                $item->setTagListByID($data['categories']);
                $item->save();
                
    			// save hashtags
    			$buzzwordList = $buzzwordManager->get();
                $buzzwordItem = $buzzwordList->getFirst();
                while ($buzzwordItem) {
                    $selected_ids = $buzzwordItem->getAllLinkedItemIDArrayLabelVersion();
                    if (in_array($buzzwordItem->getItemId(), $data['hashtags'])) {
            			$selected_ids[] = $itemId;
                        $selected_ids = array_unique($selected_ids);
                    } else {
                        $index = 0;
                        foreach ($selected_ids as $selected_id) {
                            if ($selected_id == $itemId) {
                                unset($selected_ids[$index]);
                                break;
                            }
                            $index++;
                        }
                    }
                    $buzzwordItem->saveLinksByIDArray($selected_ids);
                    $buzzwordItem = $buzzwordList->getNext();
                }
    			
            // } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            // }
            // exit;
            return $this->redirectToRoute('commsy_item_savelinks', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/savelinks")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveLinksAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        
        $tempItem = NULL;
        
        if ($item->getItemType() == 'material') {
            $tempItem = $materialService->getMaterial($itemId);
        }

        $itemArray = array($tempItem);
    
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'modifierList' => $modifierList
        );
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/copy", condition="request.isXmlHttpRequest()")
     **/
    public function copyAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $sessionItem = $legacyEnvironment->getSessionItem();

        $currentClipboardIds = array();
        if ($sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
        }

        if (!in_array($itemId, $currentClipboardIds)) {
            $currentClipboardIds[] = $itemId;
            $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
        }

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionManager->save($sessionItem);

        return new JsonResponse([
            'count' => sizeof($currentClipboardIds)
        ]);
    }
    
    private function getChoicesAsTree ($choicesArray) {
        $result = array();
        foreach ($choicesArray as $choice) {
            $result[$choice['title']] = $choice['item_id'];
            if (!empty($choice['children'])) {
                $result['children'] = $this->getChoicesAsTree($choice['children']);
            }
        }
        return $result;
    }

}
