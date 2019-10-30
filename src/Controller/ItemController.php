<?php

namespace App\Controller;

use App\Form\Model\Send;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Form\Type\SendType;
use App\Form\Type\SendListType;
use App\Form\Type\ItemDescriptionType;
use App\Form\Type\ItemCatsBuzzType;
use App\Form\Type\ItemLinksType;
use App\Form\Type\ItemWorkflowType;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Event\CommsyEditEvent;

/**
 * Class ItemController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class ItemController extends Controller
{
    /**
     * @Route("/room/{roomId}/item/{itemId}/editdescription/{draft}")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editDescriptionAction($roomId, $itemId, $draft = false, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        /** @var \cs_item $item */
        $item = $itemService->getTypedItem($itemId);
        
        $transformer = $this->get('commsy_legacy.transformer.'.$item->getItemType());

        $itemType = $item->getItemType();
        
        $formData = $transformer->transform($item);
        $formOptions = array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('app_upload_ckupload', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
            'filelistUrl' => $this->generateUrl('app_item_filelist', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        );
        
        $withRecurrence = false;
        if ($itemType == 'date') {
            /** @var \cs_dates_item $item */
            if ($item->getRecurrencePattern() != '' && !$draft) {
                $formOptions['attr']['unsetRecurrence'] = true;
                $withRecurrence = true;
            }
        }

        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($item->getLinkedItem()));
        } else {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($item));
        }

        $form = $this->createForm(ItemDescriptionType::class, $formData, $formOptions);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            if ($saveType == 'save' || $saveType == 'saveThisDate') {
                $item = $transformer->applyTransformation($item, $form->getData());
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();
                if (($item->getItemType() == CS_SECTION_TYPE) || ($item->getItemType() == CS_STEP_TYPE)) {
                    $linkedItem = $itemService->getTypedItem($item->getlinkedItemID());
                    $linkedItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $linkedItem->save();
                }
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
                throw new \UnexpectedValueException("Value must be one of 'save', 'saveThisDate' and 'saveAllDates'.");
            }

            return $this->redirectToRoute('app_item_savedescription', array('roomId' => $roomId, 'itemId' => $itemId));
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
    public function saveDescriptionAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        $itemArray = array($item);
    
        $modifierList = array();
        foreach ($itemArray as $tempItem) {
            $modifierList[$tempItem->getItemId()] = $itemService->getAdditionalEditorsForItem($tempItem);
        }

        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($item->getLinkedItem()));
        } else {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($item));
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
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $tempItem = $transformer->applyTransformation($tempItem, $form->getData());
                $tempItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $tempItem->save();
            }
            
            return $this->redirectToRoute('app_material_saveworkflow', array('roomId' => $roomId, 'itemId' => $itemId));
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

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $formData = array();
        $optionsData = array();
        $items = array();
        
        // get all items that are linked or can be linked
        $rubricInformation = $roomService->getRubricInformation($roomId);
        if (in_array('group', $rubricInformation)) {
            $rubricInformation[] = 'label';
        }

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
            $optionsData['itemsLinked'] = [];
        }
        // add number of linked items to feed amount
        $countLinked = count($optionsData['itemsLinked']);

        $itemManager->setIntervalLimit($feedAmount + $countLinked);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        // get all items except linked items
        $optionsData['items'] = [];
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();
            
        }

        $linkedItemIds = $item->getAllLinkedItemIDArray();
        foreach ($linkedItemIds as $linkedId) {
            $formData['itemsLinked'][$linkedId] = true;
        }

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($environment->getCurrentUser()->getUserId());
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                if (
                    $tempTypedItem->getType() != "discarticle" &&
                    $tempTypedItem->getType() != "task" &&
                    $tempTypedItem->getType() != 'link_item' &&
                    $tempTypedItem->getType() != 'tag' &&
                    $tempTypedItem->getType() != 'step'
                ) {
                    $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                    $i++;
                }
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        // get all categories -> tree
        $optionsData['categories'] = $this->getCategories($roomId, $this->get('commsy_legacy.category_service'));
        $formData['categories'] = $this->getLinkedCategories($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(array('min' => 1))] : array();

        // get all hashtags -> list
        $optionsData['hashtags'] = $this->getHashtags($roomId, $environment);
        $formData['hashtags'] = $this->getLinkedHashtags($itemId, $roomId, $environment);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(array('min' => 1))] : [];




        $translator = $this->get('translator');

        $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($item));

        $form = $this->createForm(ItemLinksType::class, $formData, [
            'filterRubric' => $optionsData['filterRubric'],
            'filterPublic' => $optionsData['filterPublic'],
            'items' => $optionsData['items'],
            'itemsLinked' => array_flip($optionsData['itemsLinked']),
            'itemsLatest' => array_flip($optionsData['itemsLatest']),
            'categories' => $optionsData['categories'],
            'categoryConstraints' => array(),
            'hashtags' => $optionsData['hashtags'],
            'hashtagConstraints' => array(),
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            'placeholderText' => $translator->trans('Hashtag', [], 'hashtag'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
             if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                $itemData = array_merge(array_keys($data['itemsLinked']), $data['itemsLatest']);

                // update modifier
                $item->setModificatorItem($environment->getCurrentUserItem());

                // save links
                $item->setLinkedItemsByIDArray($itemData);
                $item->setTagListByID($data['categories']);
                $item->setBuzzwordListByID($data['hashtags']);

                if ($item->getItemType() == CS_TOPIC_TYPE) {
                    if (empty($itemData)) {
                        $item->deactivatePath();
                    }
                }

                // persist
                $item->save();
            }

            return $this->redirectToRoute('app_item_savelinks', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        ];
    }


    /**
     * @Route("/room/{roomId}/item/{itemId}/editCatsBuzz/{feedAmount}", defaults={"feedAmount" = 20})
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editCatsBuzzAction($roomId, $itemId, $feedAmount, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomService = $this->get('commsy_legacy.room_service');

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $roomItem = $roomService->getRoomItem($roomId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $formData = array();
        $optionsData = array();
        $items = array();

        // get all items that are linked or can be linked
        $rubricInformation = $roomService->getRubricInformation($roomId);
        if (in_array('group', $rubricInformation)) {
            $rubricInformation[] = 'label';
        }

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
            $optionsData['itemsLinked'] = [];
        }
        // add number of linked items to feed amount
        $countLinked = count($optionsData['itemsLinked']);

        $itemManager->setIntervalLimit($feedAmount + $countLinked);
        $itemManager->select();
        $itemList = $itemManager->get();

        // get all items except linked items
        $optionsData['items'] = [];
        $tempItem = $itemList->getFirst();
        while ($tempItem) {
            $tempTypedItem = $itemService->getTypedItem($tempItem->getItemId());
            // skip already linked items
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                $optionsData['items'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                $items[$tempTypedItem->getItemId()] = $tempTypedItem;
            }
            $tempItem = $itemList->getNext();

        }

        $linkedItemIds = $item->getAllLinkedItemIDArray();
        foreach ($linkedItemIds as $linkedId) {
            $formData['itemsLinked'][$linkedId] = true;
        }

        // get latest edited items from current user
        $itemManager->setContextLimit($roomId);
        $itemManager->setUserUserIDLimit($environment->getCurrentUser()->getUserId());
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                if ($tempTypedItem->getType() != "discarticle" && $tempTypedItem->getType() != "task" && $tempTypedItem->getType() != 'link_item' && $tempTypedItem->getType() != 'tag') {
                    $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                    $i++;
                }
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        // get all categories -> tree
        $optionsData['categories'] = $this->getCategories($roomId, $this->get('commsy_legacy.category_service'));
        $formData['categories'] = $this->getLinkedCategories($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(array('min' => 1))] : array();

        // get all hashtags -> list
        $optionsData['hashtags'] = $this->getHashtags($roomId, $environment);
        $formData['hashtags'] = $this->getLinkedHashtags($itemId, $roomId, $environment);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(array('min' => 1))] : [];


        $translator = $this->get('translator');

        $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($item));

        $form = $this->createForm(ItemCatsBuzzType::class, $formData, [
            'filterRubric' => [],
            'filterPublic' => [],
            'items' => [],
            'itemsLinked' => [],
            'itemsLatest' => [],
            'categories' => $optionsData['categories'],
            'categoryConstraints' => $categoryConstraints,
            'hashtags' => $optionsData['hashtags'],
            'hashtagConstraints' => $hashtagConstraints,
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            'placeholderText' => $translator->trans('Hashtag', [], 'hashtag'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                // $itemData = array_merge(array_keys($data['itemsLinked']), $data['itemsLatest']);

                // update modifier
                $item->setModificatorItem($environment->getCurrentUserItem());

                // save links
                //$item->setLinkedItemsByIDArray($itemData);
                $item->setTagListByID($data['categories']);
                $item->setBuzzwordListByID($data['hashtags']);

                if ($item->getItemType() == CS_TOPIC_TYPE) {
                    if (empty($itemData)) {
                        $item->deactivatePath();
                    }
                }

                // persist
                $item->save();
            }

            return $this->redirectToRoute('app_item_savelinks', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return [
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form->createView(),
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        ];
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

        $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($item));

        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'modifierList' => $modifierList
        );
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
        $defaultGroupId = null;
        if(sizeof($groupChoices)>0){
            $defaultGroupId = array_values($groupChoices)[0];
        }

        $isShowGroupAllRecipients = $mailAssistant->showGroupAllRecipients($request);

        $formData = new Send();
        $formData->setAdditionalRecipients(['']);
        $formData->setSendToGroups([$defaultGroupId]);
        if($isShowGroupAllRecipients){
            $formData->setSendToGroupAll(false);
        }else{
            $formData->setSendToGroupAll(null);
        }
        $formData->setSendToAll(false);
        $formData->setMessage($mailAssistant->prepareMessage($item));
        $formData->setCopyToSender(false);


        $form = $this->createForm(SendType::class, $formData, [
            'item' => $item,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // if cancel was clicked, redirect back to detail page
            if ($form->get('cancel')->isClicked()) {

                $itemType = $item->getType();
                if ($item->getType() == 'label') {
                    $itemType = $item->getLabelType();
                }

                return $this->redirectToRoute('app_' . $itemType . '_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }

            // send mail
            $message = $mailAssistant->getSwiftMessage($form, $item, true);
            $this->get('mailer')->send($message);

            $recipientCount = count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
            $this->addFlash('recipientCount', $recipientCount);

            // redirect to success page
            return $this->redirectToRoute('app_item_sendsuccess', [
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

        $itemType = $item->getType();
        if ($item->getType() == 'label') {
            $itemType = $item->getLabelType();
        }

        return [
            'link' => $this->generateUrl('app_' . $itemType . '_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getTitle(),
        ];
    }

    private function transformTagArray($tagArray)
    {
        $array = [];

        foreach ($tagArray as $tag) {
            $array[$tag['title']] = $tag['item_id'];

            if (!empty($tag['children'])) {
                $array[$tag['title'] . 'sub'] = $this->transformTagArray($tag['children']);
            }
        }

        return $array;
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
                $optionsData['itemsLatest'][] = array(
                    'title' => $tempTypedItem->getTitle(), 
                    'text' => $tempTypedItem->getType(), 
                    'url' => '', 
                    'id' => $tempTypedItem->getItemId()
                );
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = array();
        }

        return new JsonResponse([
            $optionsData['itemsLatest']
        ]);
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

        $formMessage = $this->renderView('email/item_list_template.txt.twig',array('user' => $currentUser, 'room' => $room));

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

            $message = (new \Swift_Message())
                ->setSubject($data['subject'])
                ->setFrom(array($currentUser->getEmail() => $currentUser->getFullname()))
                ->setTo($toArray)
                ->setBody(
                    $this->renderView(
                        'email/item_list.txt.twig',
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
     * @Route("/room/{roomId}/item/{itemId}/filelist")
     * @Template()
     **/
    public function filelistAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');

        /** @var \cs_item $item */
        $item = $itemService->getItem($itemId);

        /** @var \cs_file_item[] $files */
        $files = $item->getFileList()->to_array();
        $fileArray = array();

        foreach ($files as $key => $file) {
            $fileArray[] = array (
                'name' => $file->getFileName(),
                'path' => $this->generateUrl('app_file_getfile', [
                    'fileId' => $file->getFileID(),
                ], UrlGeneratorInterface::ABSOLUTE_PATH),
                'id' => $file->getFileID(),
                'ext' => $file->getExtension(),
            );
        }

        return new JsonResponse([
            'files' => $fileArray,
        ]);

    }


    /**
     * @Route("/room/{roomId}/item/{itemId}/stepper")
     * @Template()
     **/
    public function stepperAction($roomId, $itemId, Request $request)
    {
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $itemService = $this->get('commsy_legacy.item_service');

        /** @var \cs_item $baseItem */
        $baseItem = $itemService->getItem($itemId);

        /** @var \cs_manager $rubricManager */
        $rubricManager = $environment->getManager($baseItem->getItemType());

        /** @var \cs_item $item */
        $item = $rubricManager->getItem($itemId);

        if ($baseItem->getItemType() == 'project') {
            $rubricManager->setCommunityroomLimit($roomId);
            $rubricManager->setContextLimit($environment->getCurrentPortalID());
        } else {
            $rubricManager->setContextLimit($roomId);
        }
        
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
        
        $html = $this->renderView('App:'.ucfirst($baseItem->getItemType()).':detailPrint.html.twig', [
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
     * @Route("/room/{roomId}/item/{itemId}/get", condition="request.isXmlHttpRequest()")
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function singleArticleAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id ' . $itemId);
        }

        return [
            'item' => $item,
        ];
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/links")
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function linksAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $itemCategories = $item->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $itemCategories);
        }

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        return [
            'item' => $item,
            'showHashtags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'roomCategories' => $categories,
        ];
    }

    private function getTagDetailArray ($baseCategories, $itemCategories) {
        $result = array();
        $tempResult = array();
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $tempArray = array();
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                }
            }
            $tempResult = array();
            $addCategory = false;
        }
        return $result;
    }

    public function getCategories($roomId, $categoryService) {
        $categories = $categoryService->getTags($roomId);
        return $this->transformTagArray($categories);
    }

    /**
     * @param \cs_item $item
     * @return \cs_tag_item[]
     */
    public function getLinkedCategories($item) {
        /** @var \cs_item $item */

        $linkedCategories = [];
        $categoriesList = $item->getTagList();

        /** @var \cs_tag_item $categoryItem */
        $categoryItem = $categoriesList->getFirst();
        while ($categoryItem) {
            $linkedCategories[] = $categoryItem->getItemId();
            $categoryItem = $categoriesList->getNext();
        }
        return $linkedCategories;
    }

    public function getHashtags($roomId, $legacyEnvironment) {
        $hashtags = [];

        /** @var \cs_buzzword_manager $buzzwordManager */
        $buzzwordManager = $legacyEnvironment->getBuzzwordManager();
        $buzzwordManager->setContextLimit($roomId);
        $buzzwordManager->setTypeLimit('buzzword');
        $buzzwordManager->select();
        $buzzwordList = $buzzwordManager->get();
        $buzzwordItem = $buzzwordList->getFirst();
        while ($buzzwordItem) {
            $hashtags[$buzzwordItem->getItemId()] = $buzzwordItem->getTitle();
            $buzzwordItem = $buzzwordList->getNext();
        }
        return array_flip($hashtags);
    }

    public function getLinkedHashtags($itemId, $roomId, $legacyEnvironment) {
        $linkedHashtags = [];

        /** @var \cs_buzzword_manager $buzzwordManager */
        $buzzwordManager = $legacyEnvironment->getBuzzwordManager();
        $buzzwordManager->setContextLimit($roomId);
        $buzzwordManager->setTypeLimit('buzzword');
        $buzzwordManager->select();
        $buzzwordList = $buzzwordManager->get();

        /** @var \cs_buzzword_item $buzzwordItem */
        $buzzwordItem = $buzzwordList->getFirst();
        while ($buzzwordItem) {
            $selected_ids = $buzzwordItem->getAllLinkedItemIDArrayLabelVersion();
            if (in_array($itemId, $selected_ids)) {
                $linkedHashtags[] = $buzzwordItem->getItemId();
            }
            $buzzwordItem = $buzzwordList->getNext();
        }
        return $linkedHashtags;
    }

    /**
     * @Route("/room/{roomId}/item/{itemId}/canceledit")
     * @Template()
     */
    public function cancelEditAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        
        if ($item->getItemType() === CS_SECTION_TYPE ||$item->getItemType() === CS_STEP_TYPE) {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::CANCEL, new CommsyEditEvent($item->getLinkedItem()));
        } else {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::CANCEL, new CommsyEditEvent($item));
        }

        return array(
            'canceledEdit' => true,
            'roomId' => $roomId,
            'item' => $item,
        );
    }
}
