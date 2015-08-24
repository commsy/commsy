<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

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
            
            return $this->redirectToRoute('commsy_item_saveworkflow', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'item' => $tempItem,
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/saveworkflow")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveWorkflowAction($roomId, $itemId, Request $request)
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
     * @Route("/room/{roomId}/item/{itemId}/editlinks")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editLinksAction($roomId, $itemId, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getTypedItem($itemId);
        
        $formData = array();
        $optionsData = array();
        
        // get all items that are linked or can be linked
        
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
            'categories' => $optionsData['categories'],
            'hashtags' => $optionsData['hashtags']
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // ToDo ...
                $data = $form->getData();
                
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
    			
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            
            return $this->redirectToRoute('commsy_item_savelinks', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'categories' => $categories,
            'form' => $form->createView()
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
