<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Form\Type\SendType;
use CommsyBundle\Form\Type\SendListType;
use CommsyBundle\Form\Type\ItemDescriptionType;
use CommsyBundle\Form\Type\ItemLinksType;
use CommsyBundle\Form\Type\ItemWorkflowType;

class ItemController extends Controller
{
    /**
     * @Route("/room/{roomId}/item/{itemId}/editdescription")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editDescriptionAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        
        $transformer = $this->get('commsy_legacy.transformer.'.$item->getItemType());
        
        $formData = array();
        $itemType = $item->getItemType();
        
        $formData = $transformer->transform($item);
        $formOptions = array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('commsy_upload_ckupload', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        );
        
        $withRecurrence = false;
        if ($itemType == 'date') {
            if ($item->getRecurrencePattern() != '') {
                $formOptions['attr']['unsetRecurrence'] = true;
                $withRecurrence = true;
            }
        }
        
        $form = $this->createForm(ItemDescriptionType::class, $formData, $formOptions);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            if ($saveType == 'save' || $saveType == 'saveThisDate') {
                $item = $transformer->applyTransformation($item, $form->getData());
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();
            } else if ($saveType == 'saveAllDates') {
                $dateService = $this->get('commsy_legacy.date_service');
                $datesArray = $dateService->getRecurringDates($item->getContextId(), $item->getRecurrenceId());
                $formData = $form->getData();
                $item = $transformer->applyTransformation($item, $formData);
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();
                foreach ($datesArray as $tempDate) {
                    $tempDate->setDescription($item->getDescription());
                    $tempDate->save();
                }
            } else {
                throw new UnexpectedValueException("Value must be one of 'save', 'saveThisDate' and 'saveAllDates'.");
            }
            
            return $this->redirectToRoute('commsy_item_savedescription', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        // etherpad
        $isMaterial = false;
        if ($itemType == "material") {
            $isMaterial = true;
        }

        return array(
            'isMaterial' => $isMaterial,
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'withRecurrence' => $withRecurrence,
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/savedescription")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveDescriptionAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        $itemArray = array($item);
    
        $modifierList = array();
        foreach ($itemArray as $tempItem) {
            $modifierList[$tempItem->getItemId()] = $itemService->getAdditionalEditorsForItem($tempItem);
        }
        
        return array(
            // etherpad subscriber (material save)
            // important: save and item->id parameter are needed
            'save' => true,
            'roomId' => $roomId,
            'item' => $item,
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
        $roomService = $this->get('commsy_legacy.room_service');
        $room = $roomService->getRoomItem($roomId);

        $itemService = $this->get('commsy_legacy.item_service');
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
        
        $form = $this->createForm(ItemWorkflowType::class, $formData, array());
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $tempItem = $transformer->applyTransformation($tempItem, $form->getData());
                $tempItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
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
        $workflowData['withTrafficLight'] = $room->withWorkflowTrafficLight();
        $workflowData['withResubmission'] = $room->withWorkflowResubmission();
        $workflowData['workflowValidity'] = $room->withWorkflowValidity();


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
        $roomService = $this->get('commsy_legacy.room_service');
        
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $roomItem = $roomService->getRoomItem($roomId);
        
        $formData = array();
        $optionsData = array();
        $items = array();
        
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
                $items[$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem;
            } else {
                $optionsData['itemsLinked'][$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem->getFullname();
                $items[$tempTypedLinkedItem->getItemId()] = $tempTypedLinkedItem;
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
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();
            
        }

        $formData['itemsLinked'] = $item->getAllLinkedItemIDArray();

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($environment->getCurrentUser()->getUserId());
        // $itemManager->setIntervalLimit(10);
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && !array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) {
                $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $i++;
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = array();
        }
        
        // get all categories -> tree
        $categoryService = $this->get('commsy_legacy.category_service');
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

        $form = $this->createForm(ItemLinksType::class, $formData, array(
            'filterRubric' => $optionsData['filterRubric'],
            'filterPublic' => $optionsData['filterPublic'],
            'items' => $optionsData['items'],
            'itemsLinked' => array_flip($optionsData['itemsLinked']),
            'itemsLatest' => array_flip($optionsData['itemsLatest']),
            'categories' => $optionsData['categories'],
            'hashtags' => array_flip($optionsData['hashtags'])
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
             if ($form->get('save')->isClicked()) {
                // ToDo ...
                $data = $form->getData();

                $itemData = array_merge($data['itemsLinked'], $data['itemsLatest']);

                // save linked entries
                $item->setLinkedItemsByIDArray($itemData);

                $item->setModificatorItem($environment->getCurrentUserItem());

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
                //ToDo ...
            }
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
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/savelinks")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveLinksAction($roomId, $itemId, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $itemService = $this->get('commsy_legacy.item_service');
        $tempItem = $itemService->getTypedItem($itemId);

        $itemArray = array($tempItem);
    
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'modifierList' => $modifierList
        );
    }

    /**
     * @Route("/room/{roomId}/item/copy", condition="request.isXmlHttpRequest()")
     **/
    public function copyAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $sessionItem = $legacyEnvironment->getSessionItem();

        $currentClipboardIds = array();
        if ($sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
        }

        // extract ids from request data
        $requestContent = $request->getContent();
        if (empty($requestContent)) {
            throw new \Exception('no request content given');
        }

        $jsonArray = json_decode($requestContent, true);

        if (!isset($jsonArray['itemIds']) || empty($jsonArray['itemIds'])) {
            throw new \Exception('no item ids given');
        }

        $itemIds = $jsonArray['itemIds'];

        foreach ($itemIds as $itemId) {
            if (!in_array($itemId, $currentClipboardIds)) {
                $currentClipboardIds[] = $itemId;
                $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
            }
        }

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionManager->save($sessionItem);

        return new JsonResponse([
            'count' => sizeof($currentClipboardIds)
        ]);
    }

    /**
     * @Route("/room/{roomId}/{itemId}/send")
     * @Template()
     **/
    public function sendAction($roomId, $itemId, Request $request)
    {
        // get item
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        // prepare form
        $mailAssistant = $this->get('commsy.utils.mail_assistant');

        $groupChoices = $mailAssistant->getGroupChoices($item);
        $defaultGroupId = array_values($groupChoices)[0];

        $formData = [
            'additional_recipients' => [
                '',
            ],
            'send_to_groups' => [
                $defaultGroupId
            ],
            'send_to_group_all' => false,
            'send_to_all' => false,
            'message' => $mailAssistant->prepareMessage($item),
            'copy_to_sender' => false,
        ];

        $form = $this->createForm(SendType::class, $formData, [
            'item' => $item,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // if cancel was clicked, redirect back to detail page
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('commsy_' . $item->getType() . '_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }

            // send mail
            $message = $mailAssistant->getSwiftMessage($form->getData(), $item);
            $this->get('mailer')->send($message);

            // redirect to success page
            return $this->redirectToRoute('commsy_item_sendsuccess', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/room/{roomId}/{itemId}/send/success")
     * @Template()
     **/
    public function sendSuccessAction($roomId, $itemId)
    {
        // get item
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        return [
            'link' => $this->generateUrl('commsy_' . $item->getType() . '_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getTitle(),
        ];
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

    /**
     * @Route("/room/{roomId}/item/{itemId}/autocomplete/{feedAmount}", defaults={"feedAmount" = 20})
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function autocompleteAction($roomId, $itemId, $feedAmount, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');
        
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $roomItem = $roomService->getRoomItem($roomId);
        
        $formData = array();
        $optionsData = array();
        $items = array();
        
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


        $itemManager->setIntervalLimit($feedAmount);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        // get all items except linked items
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();
            
        }

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($environment->getCurrentUser()->getUserId());
        $itemManager->setIntervalLimit(10);
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $latestItem = $latestItemList->getFirst();
        while ($latestItem) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem) {
                $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = array();
        }

        return new JsonResponse([
            $optionsData['itemsLatest']
        ]);

        // return array(
        //     'itemId' => $itemId,
        //     'roomId' => $roomId,
        //     'form' => $form->createView(),
        //     'showCategories' => $roomItem->withTags(),
        //     'showHashtags' => $roomItem->withBuzzwords(),
        //     'items' => $items,
        // );
    }

    /**
     * @Route("/room/{roomId}/item/sendlist", condition="request.isXmlHttpRequest()")
     * @Template()
     **/
    public function sendlistAction($roomId, Request $request)
    {
        // extract item id from request data
        $requestContent = $request->getContent();
        if (empty($requestContent)) {
            throw new \Exception('no request content given');
        }
        
        $roomService = $this->get('commsy_legacy.room_service');
        $room = $roomService->getRoomItem($roomId);

        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $environment->getCurrentUser();

        $jsonArray = json_decode($requestContent, true);

        // prepare form
        $mailAssistant = $this->get('commsy.utils.mail_assistant');

        $formMessage = $this->renderView('CommsyBundle:Email:itemListTemplate.txt.twig',array('user' => $currentUser, 'room' => $room));

        $formData = [
            'message' => $formMessage,
        ];

        $form = $this->createForm(SendListType::class, $formData, []);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService = $this->get('commsy_legacy.user_service');
            
            $data = $form->getData();

            $userIds = explode(',', $data['entries']);
            $toArray = array();
            foreach ($userIds as $userId) {
                $user = $userService->getUser($userId);
                if ($user) {
                    $toArray[$user->getEmail()] = $user->getFullname();
                }
            }

            $message = \Swift_Message::newInstance()
                ->setSubject($data['subject'])
                ->setFrom(array($currentUser->getEmail() => $currentUser->getFullname()))
                ->setTo($toArray)
                ->setBody(
                    $this->renderView(
                        'CommsyBundle:Email:itemList.txt.twig',
                        array('message' => strip_tags($data['message']))
                    ),
                    'text/plain'
                )
            ;
            
            if ($data['copy_to_sender']) {
                $message->setCc(array($currentUser->getEmail() => $currentUser->getFullname()));
            }
            
            $this->get('mailer')->send($message);

            return new JsonResponse([
                'message' => 'send ...',
                'timeout' => '5550',
                'layout' => 'cs-notify-message',
                'data' => NULL,
            ]);
        }

        return [
            'form' => $form->createView()
        ];
    }


    /**
     * @Route("/room/{roomId}/item/{itemId}/stepper")
     * @Template()
     **/
    public function stepperAction($roomId, $itemId, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $itemService = $this->get('commsy_legacy.item_service');
        $baseItem = $itemService->getItem($itemId);
        
        $rubricManager = $environment->getManager($baseItem->getItemType());
        
        $item = $rubricManager->getItem($itemId);
        
        $rubricManager->setContextLimit($roomId);
        
        if ($item->getItemType() == 'date') {
            $rubricManager->setWithoutDateModeLimit();
        }
        
        $rubricManager->select();
        $itemList = $rubricManager->get();
        $items = $itemList->to_array();
        
        $itemList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundItem = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($items as $tempItem) {
            if (!$foundItem) {
                if ($counterBefore > 5) {
                    array_shift($itemList);
                } else {
                    $counterBefore++;
                }
                $itemList[] = $tempItem;
                if ($tempItem->getItemID() == $item->getItemID()) {
                    $foundItem = true;
                }
                if (!$foundItem) {
                    $prevItemId = $tempItem->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $itemList[] = $tempItem;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempItem->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($items)) {
            if ($prevItemId) {
                $firstItemId = $items[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $items[sizeof($items)-1]->getItemId();
            }
        }
        
        return array(
            'rubric' => $item->getItemType(),
            'roomId' => $roomId,
            'itemList' => $itemList,
            'item' => $item,
            'counterPosition' => $counterPosition,
            'count' => sizeof($items),
            'firstItemId' => $firstItemId,
            'prevItemId' => $prevItemId,
            'nextItemId' => $nextItemId,
            'lastItemId' => $lastItemId,
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/print")
     */
    public function printAction($roomId, $itemId)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $itemService = $this->get('commsy_legacy.item_service');
        $baseItem = $itemService->getItem($itemId);
        
        $html = $this->renderView('CommsyBundle:'.ucfirst($baseItem->getItemType()).':detailPrint.html.twig', [
        ]);

        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="print.pdf"'
            ]
        );
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/download")
     */
    public function downloadAction($roomId, $itemId)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $itemService = $this->get('commsy_legacy.item_service');
        $baseItem = $itemService->getItem($itemId);
        
        $downloadService = $this->get('commsy_legacy.download_service');
        
        $zipFile = $downloadService->zipFile($roomId, $itemId);

        $response = new BinaryFileResponse($zipFile);
        $response->deleteFileAfterSend(true);

        $filename = 'CommSy_'.ucfirst($baseItem->getItemType()).'.zip';
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }
    
    /**
     * @Route("/room/{roomId}/item/{itemId}/delete")
     * @Security("is_granted('ITEM_EDIT', itemId)")
     **/
    public function deleteAction($roomId, $itemId, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        
        $item->delete();

        $route = 'commsy_'.$item->getItemType().'_list';
        if ($item->getItemType() == 'date') {
            $roomService = $this->get('commsy_legacy.room_service');
            $room = $roomService->getRoomItem($roomId);
            if ($room->getDatesPresentationStatus() != 'normal') {
                $route = 'commsy_date_calendar';
            }
        }

        if ($item->getItemType() == 'section') {
            $route = 'commsy_material_detail';
            $materialService = $this->get('commsy_legacy.material_service');
            $section = $materialService->getSection($item->getItemID());
            return $this->redirectToRoute($route, array('roomId' => $roomId, 'itemId' => $section->getLinkedItemID()));
        }

        if ($item->getItemType() == 'step') {
            $route = 'commsy_todo_detail';
            $todoService = $this->get('commsy_legacy.todo_service');
            $step = $todoService->getStep($item->getItemID());
            return $this->redirectToRoute($route, array('roomId' => $roomId, 'itemId' => $step->getTodoID()));
        }

        return $this->redirectToRoute($route, array('roomId' => $roomId));
    }
}
