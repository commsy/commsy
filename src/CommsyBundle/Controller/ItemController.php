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
            'items' => $items,
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
     * @Route("/room/{roomId}/item/send", condition="request.isXmlHttpRequest()")
     * @Template()
     **/
    public function sendAction($roomId, Request $request)
    {
        // extract item id from request data
        $requestContent = $request->getContent();
        if (empty($requestContent)) {
            throw new \Exception('no request content given');
        }

        $jsonArray = json_decode($requestContent, true);

        if (!isset($jsonArray['itemId']) || empty($jsonArray['itemId'])) {
            throw new \Exception('no item id given');
        }

        $itemId = $jsonArray['itemId'];

        // get item
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        // prepare form
        $formData = [
            'additional_recipients' => [
                'a', 'b', 'c'
            ]
        ];

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $translatorService = $this->get('translator');

        // message
        $currentContextItem = $legacyEnvironment->getCurrentContextItem();

        $content = '';
        $link = '';

        $contextTitle = $currentContextItem->getTitle();
        if ($legacyEnvironment->inProjectRoom()) {
            $message = $translatorService->trans('This email was sent from project room...', ['%room_name%' => $contextTitle], 'mail');
        } elseif ($legacyEnvironment->inGroupRoom()) {
            $message = $translatorService->trans('This email was sent from group room...', ['%room_name%' => $contextTitle], 'mail');
        } else {
            $message = $translatorService->trans('This email was sent from community room...', ['%room_name%' => $contextTitle], 'mail');
        }

        if (empty($content)) {
            $message .= '<br/><br/>';
        } else {
            $message .= $content;
        }

        $message .= '<br/>---<br/><a href="' . $link . '">' . $link . '</a>';

        //$response['body'] = strip_tags($emailtext);
        $formData['message'] = $message;

        $form = $this->createForm('send', $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        return [
            'form' => $form->createView()
        ];
    }



    /*

    public function actionInit() {
        
        $current_user = $this->_environment->getCurrentUserItem();
        $translator = $this->_environment->getTranslationObject();

        // context information
        $contextInformation = array();
        $contextInformation["name"] = $current_context->getTitle();
        $response['context'] = $contextInformation;

        // group information
        $groupArray = $this->getAllLabelsByType("group");

        // institutions information
        $institutionArray = $this->getAllLabelsByType("institution");

        // Wenn man mit HTTPS auf Commsy surft und eine Email generiert
        // sollte diese Mail auch https links erstellen.
        if ( !empty($_SERVER["HTTPS"])
                and $_SERVER["HTTPS"]
        ) {
            $url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']
            .'?cid='.$this->_environment->getCurrentContextID()
            .'&mod='.$link_module
            .'&fct=detail'
            .'&iid='.$item->getItemID();
        } else {
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']
            .'?cid='.$this->_environment->getCurrentContextID()
            .'&mod='.$link_module
            .'&fct=detail'
            .'&iid='.$item->getItemID();
        }
        $link = $url;

        $content = '';
        //generate module name for the interface- a pretty version of module...
        if ($module== CS_DATE_TYPE) {
            // set up style of days and times
            $parse_time_start = convertTimeFromInput($item->getStartingTime());
            $conforms = $parse_time_start['conforms'];
            if ($conforms == TRUE) {
                $start_time_print = getTimeLanguage($parse_time_start['datetime']);
            } else {
                $start_time_print = $item->getStartingTime();
            }

            $parse_time_end = convertTimeFromInput($item->getEndingTime());
            $conforms = $parse_time_end['conforms'];
            if ($conforms == TRUE) {
                $end_time_print = getTimeLanguage($parse_time_end['datetime']);
            } else {
                $end_time_print = $item->getEndingTime();
            }

            $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
            $conforms = $parse_day_start['conforms'];
            if ($conforms == TRUE) {
                $start_day_print = getDateInLang($parse_day_start['datetime']);
            } else {
                $start_day_print = $item->getStartingDay();
            }

            $parse_day_end = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
            $conforms = $parse_day_end['conforms'];
            if ($conforms == TRUE) {
                $end_day_print =getDateLanguage($parse_day_end['datetime']);
            } else {
                $end_day_print =$item->getEndingDay();
            }
            //formating dates and times for displaying
            $date_print ="";
            $time_print ="";

            if ($end_day_print != "") { //with ending day
                $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$translator->getMessage('DATES_TILL').' '.$end_day_print;
                if ($parse_day_start['conforms']
                        and $parse_day_end['conforms']) { //start and end are dates, not strings
                    $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
                }
                if ($start_time_print != "" and $end_time_print =="") { //starting time given
                    $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
                    if ($parse_time_start['conforms'] == true) {
                        $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
                    $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
                    if ($parse_time_end['conforms'] == true) {
                        $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
                    if ($parse_time_end['conforms'] == true) {
                        $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                    if ($parse_time_start['conforms'] == true) {
                        $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                    $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.'<br />'.
                            $translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
                    if ($parse_day_start['conforms']
                            and $parse_day_end['conforms']) {
                        $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
                    }
                }

            } else { //without ending day
                $date_print = $start_day_print;
                if ($start_time_print != "" and $end_time_print =="") { //starting time given
                    $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
                    if ($parse_time_start['conforms'] == true) {
                        $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
                    $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
                    if ($parse_time_end['conforms'] == true) {
                        $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
                    if ($parse_time_end['conforms'] == true) {
                        $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                    if ($parse_time_start['conforms'] == true) {
                        $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                    }
                    $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
                }
            }

            if ($parse_day_start['timestamp'] == $parse_day_end['timestamp'] and $parse_day_start['conforms'] and $parse_day_end['conforms']) {
                $date_print = $translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
                if ($start_time_print != "" and $end_time_print =="") { //starting time given
                    $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
                } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
                    $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
                } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
                    $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
                }
            }
            // Date and time
            $dates_content = '';
            $dates_content = $translator->getMessage('DATES_DATETIME').': '.$item_name.LF;
            if ($time_print != '') {
                $dates_content .= $translator->getMessage('COMMON_TIME').': '.$date_print.','.$time_print.LF;
            } else {
                $dates_content .= $translator->getMessage('COMMON_TIME').': '.$date_print.LF;
            }
            // Place
            $place = $item->getPlace();
            if (!empty($place)) {
                $dates_content .= $translator->getMessage('DATES_PLACE').': ';
                $dates_content .= $place.LF;
            }
            $content = $dates_content;
        } elseif ($module== 'discussion' or $module== 'discussions') {
            $discussion_content = $translator->getMessage('COMMON_DISCUSSION').': '.$item->getTitle().LF;
            $article_count = $item->getAllArticlesCount();
            $discussion_content .= $translator->getMessage('DISCUSSION_DISCARTICLE_COUNT').': '.$article_count.LF;
            $time = $item->getLatestArticleModificationDate();
            $discussion_content .= $translator->getMessage('DISCUSSION_LAST_ENTRY').': '.getDateTimeInLang($time).LF;
            $content = $discussion_content;
        } elseif ($module== 'material' or $module== 'materials') {
            $material_content = $translator->getMessage('COMMON_MATERIAL').': '.$item->getTitle().LF;
            $content = $material_content;
        } elseif ($module== 'announcement' or $module== CS_ANNOUNCEMENT_TYPE) {
            $announcement_content = $translator->getMessage('COMMON_ANNOUNCEMENT').': '.$item->getTitle().LF;
            $content = $announcement_content;
        }  elseif ($module== 'label' or $module== 'labels') {
            $label_manager = $this->_environment->getLabelManager();
            $label = $label_manager->getItem($iid);
            $module= $label->getLabelType();
            if ($module== 'group' or $module== 'groups') {
                $group_content = $translator->getMessage('COMMON_GROUP').': '.$item->getTitle().LF;
                $content = $group_content;
            } elseif ($module== 'institution' or $module== 'institutions') {
                $institution_content = $translator->getMessage('INSTITUTION').': '.$item->getTitle().LF;
                $content = $institution_content;
            }
        }

        // receiver
        $showAttendees = false;

        if ($module === CS_DATE_TYPE) {
            $showAttendees = true;
            $attendeeType = CS_DATE_TYPE;
        }
        if ($module === CS_TODO_TYPE) {
            $showAttendees = true;
            $attendeeType = CS_TODO_TYPE;
        }
        
        $response['showAttendees'] = $showAttendees;
        $response['attendeeType'] = $attendeeType;


        $showGroupRecipients = false;
        $showInstitutionRecipients = false;
        if ( $this->_environment->inProjectRoom() and !empty($groupArray) ) {
            if ( $current_context->withRubric(CS_GROUP_TYPE) ) {
                $showGroupRecipients = true;
            }
        } else {
            if ( $current_context->withRubric(CS_INSTITUTION_TYPE) and !empty($institutionArray) ) {
                $showInstitutionRecipients = true;
            }
        }

        //Projectroom and no groups enabled -> send mails to group all
        $withGroups = true;
        if ( $current_context->isProjectRoom() && !$current_context->withRubric(CS_GROUP_TYPE)) {
            $showGroupRecipients = true;
            $withGroups = false;

            // get number of users
            $cid = $this->_environment->getCurrentContextId();
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setUserLimit();
            $user_manager->setContextLimit($cid);
            $count = $user_manager->getCountAll();
         $response['numMebers'] = $count;

            $groupArray = array_slice($groupArray, 0, 1);
        }
        
        $response['showGroupRecipients'] = $showGroupRecipients;
        $response['withGroups'] = $withGroups;
        $response['groups'] = $groupArray;

        $allMembers = false;
        if ( ($current_context->isCommunityRoom() && !$current_context->withRubric(CS_INSTITUTION_TYPE)) || $current_context->isGroupRoom()) {
            $allMembers = true;

            // get number of users
            $cid = $this->_environment->getCurrentContextId();
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setUserLimit();
            $user_manager->setContextLimit($cid);
            $count = $user_manager->getCountAll();
            
            $response['numMebers'] = $count;
        }
        
        $response['showInstitutionRecipients'] = $showInstitutionRecipients;
        $response['institutions'] = $institutionArray;
        $response['allMembers'] = $allMembers;
        
        $this->setSuccessfullDataReturn($response);
        echo $this->_return;
    }

    */
    
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
        $roomService = $this->get('commsy.room_service');
        
        $itemService = $this->get('commsy.item_service');
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

}
