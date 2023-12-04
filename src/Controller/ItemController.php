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

use App\Event\CommsyEditEvent;
use App\Form\DataTransformer\ItemTransformer;
use App\Form\DataTransformer\TransformerManager;
use App\Form\Model\Send;
use App\Form\Type\ItemCatsBuzzType;
use App\Form\Type\ItemDescriptionType;
use App\Form\Type\ItemLinksType;
use App\Form\Type\ItemWorkflowType;
use App\Form\Type\SendType;
use App\Mail\Mailer;
use App\Services\EtherpadService;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\DateService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\MailAssistant;
use App\Utils\MaterialService;
use App\Utils\RoomService;
use cs_dates_item;
use cs_file_item;
use cs_item;
use cs_label_item;
use cs_labels_manager;
use cs_manager;
use cs_material_item;
use cs_section_item;
use cs_step_item;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnexpectedValueException;

/**
 * Class ItemController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class ItemController extends AbstractController
{
    #[Route(path: '/room/{roomId}/item/{itemId}/editdescription/{draft}')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editDescription(
        DateService $dateService,
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        TransformerManager $transformerManager,
        ParameterBagInterface $parameterBag,
        MaterialService $materialService,
        EtherpadService $etherpadService,
        Request $request,
        int $roomId,
        int $itemId,
        bool $draft = false
    ): Response {
        /** @var cs_item $item */
        $item = $itemService->getTypedItem($itemId);

        $transformer = $transformerManager->getConverter($item->getItemType());

        $itemType = $item->getItemType();

        // User description in the users profile (ProfileController)
        if ($itemType === 'user') {
            throw new LogicException('user description is not handled by this method');
        }

        // NOTE: we disable the CommSy-related & MathJax toolbar items for users & groups, so their CKEEditor controls
        // won't allow any media upload; this is done since user & group detail views currently have no means to manage
        // (e.g. delete again) any attached files
        $configName = match ($itemType) {
            'group' => 'cs_item_nomedia_config',
            'discarticle' => 'cs_annotation_config',
            default => 'cs_item_config',
        };

        $url = $this->generateUrl('app_upload_ckupload', [
            'roomId' => $roomId,
            'itemId' => $itemId,
            'CKEditorFuncNum' => 42,
            'command' => 'QuickUpload',
            'type' => 'Images',
        ]);

        $formData = $transformer->transform($item);
        $formOptions = [
            'itemId' => $itemId,
            'configName' => $configName,
            'uploadUrl' => $url,
            'filelistUrl' => $this->generateUrl('app_item_filelist', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'lock_protection' => !$draft,
        ];

        $withRecurrence = false;
        if ('date' == $itemType) {
            /** @var cs_dates_item $item */
            if ('' != $item->getRecurrencePattern() && !$draft) {
                $formOptions['attr']['unsetRecurrence'] = true;
                $withRecurrence = true;
            }
        }

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        $useEtherpad = false;
        if ($parameterBag->get('commsy.etherpad.enabled')) {
            /** @var cs_material_item $materialItem */
            $materialItem = $materialService->getMaterial($itemId);
            $useEtherpad = $materialItem && $materialItem->getEtherpadEditor();
        }

        $form = $this->createForm(ItemDescriptionType::class, $formData, $formOptions);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            $legacyEnvironment = $environment->getEnvironment();
            if ('save' == $saveType || 'saveThisDate' == $saveType) {
                $item = $transformer->applyTransformation($item, $form->getData());
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                if ($item->getItemType() == CS_MATERIAL_TYPE) {
                    /** @var $item cs_material_item */
                    if ($item->getEtherpadEditor() && $item->getEtherpadEditorID()) {
                        // get description text from etherpad
                        $client = $etherpadService->getClient();

                        // get pad and get text from pad
                        $textObject = $client->getHTML($item->getEtherpadEditorID());

                        // save etherpad text to material description
                        $item->setDescription(nl2br($textObject->html));
                    }
                }

                $item->save();
                if ((CS_SECTION_TYPE == $item->getItemType()) || (CS_STEP_TYPE == $item->getItemType())) {
                    /** @var $item cs_section_item|cs_step_item */
                    $linkedItem = $itemService->getTypedItem($item->getlinkedItemID());
                    $linkedItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $linkedItem->save();
                }
            } elseif ('saveAllDates' == $saveType) {
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

            return $this->redirectToRoute('app_item_savedescription', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return $this->render('item/edit_description.html.twig', [
            'useEtherpad' => $useEtherpad,
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form,
            'withRecurrence' => $withRecurrence,
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/savedescription')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function saveDescription(
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);
        $itemArray = [$item];

        $modifierList = [];
        foreach ($itemArray as $tempItem) {
            $modifierList[$tempItem->getItemId()] = $itemService->getAdditionalEditorsForItem($tempItem);
        }

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);

        return $this->render('item/save_description.html.twig', [
            // etherpad subscriber (material save)
            // important: save and item->id parameter are needed
            'save' => true,
            'roomId' => $roomId,
            'item' => $item,
            'modifierList' => $modifierList,
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/editworkflow')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editWorkflow(
        RoomService $roomService,
        ItemService $itemService,
        MaterialService $materialService,
        ItemTransformer $transformer,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId
    ): Response {
        $workflowData = [];
        $room = $roomService->getRoomItem($roomId);
        $item = $itemService->getItem($itemId);

        $formData = [];
        $tempItem = null;

        if ('material' == $item->getItemType()) {
            // get material from MaterialService
            $tempItem = $materialService->getMaterial($itemId);
            if (!$tempItem) {
                throw $this->createNotFoundException('No material found for id '.$roomId);
            }
            $formData = $transformer->transform($tempItem);
        }

        $form = $this->createForm(ItemWorkflowType::class, $formData, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $environment->getEnvironment();
                $tempItem = $transformer->applyTransformation($tempItem, $form->getData());
                $tempItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $tempItem->save();
            }

            return $this->redirectToRoute('app_material_saveworkflow', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        $workflowData['textGreen'] = $room->getWorkflowTrafficLightTextGreen();
        $workflowData['textYellow'] = $room->getWorkflowTrafficLightTextYellow();
        $workflowData['textRed'] = $room->getWorkflowTrafficLightTextRed();
        $workflowData['withTrafficLight'] = $room->withWorkflowTrafficLight();
        $workflowData['withResubmission'] = $room->withWorkflowResubmission();
        $workflowData['workflowValidity'] = $room->withWorkflowValidity();

        return $this->render('item/edit_workflow.html.twig', ['item' => $tempItem, 'form' => $form, 'workflow' => $workflowData]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/editlinks/{feedAmount}', defaults: ['feedAmount' => 20])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editLinks(
        LabelService $labelService,
        RoomService $roomService,
        ItemService $itemService,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId,
        int $feedAmount
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        $item = $itemService->getTypedItem($itemId);
        $roomItem = $roomService->getRoomItem($roomId);

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $formData = [];
        $optionsData = [];
        $items = [];

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

        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);
        $itemManager->setTypeArrayLimit($rubricInformation);

        // get all linked items
        $itemLinkedList = $itemManager->getItemList($item->getAllLinkedItemIDArray());
        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $itemService->getTypedItem($tempLinkedItem->getItemId());
            if ('user' != $tempTypedLinkedItem->getItemType()) {
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
        $itemManager->setUserUserIDLimit($legacyEnvironment->getCurrentUser()->getUserId());
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                if (
                    'discarticle' != $tempTypedItem->getType() &&
                    'task' != $tempTypedItem->getType() &&
                    'link_item' != $tempTypedItem->getType() &&
                    'tag' != $tempTypedItem->getType() &&
                    'step' != $tempTypedItem->getType()
                ) {
                    $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                    ++$i;
                }
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        // get all categories -> tree
        $optionsData['categories'] = $labelService->getCategories($roomId);
        $formData['categories'] = $labelService->getLinkedCategoryIds($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(['min' => 1])] : [];

        // get all hashtags -> list
        $optionsData['hashtags'] = $labelService->getHashtags($roomId);
        $formData['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(['min' => 1])] : [];

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        $form = $this->createForm(ItemLinksType::class, $formData, [
            'filterRubric' => $optionsData['filterRubric'],
            'filterPublic' => $optionsData['filterPublic'],
            'items' => $optionsData['items'],
            'itemsLinked' => array_flip($optionsData['itemsLinked']),
            'itemsLatest' => array_flip($optionsData['itemsLatest']),
            'categories' => $optionsData['categories'],
            'categoryConstraints' => [],
            'hashtags' => $optionsData['hashtags'],
            'hashtagConstraints' => [],
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            'placeholderText' => $translator->trans('Hashtag', [], 'hashtag'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                $itemData = array_merge(array_keys($data['itemsLinked']), $data['itemsLatest']);

                // update modifier
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // save links
                $item->setLinkedItemsByIDArray($itemData);
                $item->setTagListByID($data['categories']);
                $item->setBuzzwordListByID($data['hashtags']);

                if (CS_TOPIC_TYPE == $item->getItemType()) {
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

        return $this->render('item/edit_links.html.twig', [
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form,
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/editCatsBuzz/{feedAmount}', defaults: ['feedAmount' => 20])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editCatsBuzz(
        CategoryService $categoryService,
        LabelService $labelService,
        RoomService $roomService,
        ItemService $itemService,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        Request $request,
        int $roomId,
        int $itemId,
        int $feedAmount
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        $item = $itemService->getTypedItem($itemId);
        $roomItem = $roomService->getRoomItem($roomId);

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $formData = [];
        $optionsData = [];
        $items = [];

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

        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);
        $itemManager->setTypeArrayLimit($rubricInformation);

        // get all linked items
        $itemLinkedList = $itemManager->getItemList($item->getAllLinkedItemIDArray());
        $tempLinkedItem = $itemLinkedList->getFirst();
        while ($tempLinkedItem) {
            $tempTypedLinkedItem = $itemService->getTypedItem($tempLinkedItem->getItemId());
            if ('user' != $tempTypedLinkedItem->getItemType()) {
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
        $itemManager->setUserUserIDLimit($legacyEnvironment->getCurrentUser()->getUserId());
        $itemManager->select();
        $latestItemList = $itemManager->get();

        $i = 0;
        $latestItem = $latestItemList->getFirst();
        while ($latestItem && $i < 5) {
            $tempTypedItem = $itemService->getTypedItem($latestItem->getItemId());
            if ($tempTypedItem && (!array_key_exists($tempTypedItem->getItemId(), $optionsData['itemsLinked'])) && ($tempTypedItem->getItemId() != $itemId)) {
                if ('discarticle' != $tempTypedItem->getType() && 'task' != $tempTypedItem->getType() && 'link_item' != $tempTypedItem->getType() && 'tag' != $tempTypedItem->getType()) {
                    $optionsData['itemsLatest'][$tempTypedItem->getItemId()] = $tempTypedItem->getTitle();
                    ++$i;
                }
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        // get all categories -> tree
        $optionsData['categories'] = $labelService->getCategories($roomId);
        $formData['categories'] = $labelService->getLinkedCategoryIds($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(['min' => 1])] : [];

        // get all hashtags -> list
        $optionsData['hashtags'] = $labelService->getHashtags($roomId);
        $formData['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(['min' => 1])] : [];

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

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
            'placeholderTextCategories' => $translator->trans('New category', [], 'category'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                // $itemData = array_merge(array_keys($data['itemsLinked']), $data['itemsLatest']);
                if ($data['newCategory']) {
                    $data['categories'][] = $categoryService->addTag($data['newCategory'], $roomId)->getItemID();
                }

                // update modifier
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                // save links
                // $item->setLinkedItemsByIDArray($itemData);
                $item->setTagListByID($data['categories']);
                $item->setBuzzwordListByID($data['hashtags']);

                if (CS_TOPIC_TYPE == $item->getItemType()) {
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

        return $this->render('item/edit_cats_buzz.html.twig', [
            'itemId' => $itemId,
            'roomId' => $roomId,
            'form' => $form,
            'showCategories' => $roomItem->withTags(),
            'showHashtags' => $roomItem->withBuzzwords(),
            'items' => $items,
            'itemsLatest' => $optionsData['itemsLatest'],
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/savelinks')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function saveLinks(
        RoomService $roomService,
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ): Response {
        $roomItem = $roomService->getRoomItem($roomId);
        $tempItem = $itemService->getTypedItem($itemId);

        $itemArray = [$tempItem];

        $modifierList = [];
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);

        return $this->render('item/save_links.html.twig', ['roomId' => $roomId, 'item' => $tempItem, 'showHashTags' => $roomItem->withBuzzwords(), 'showCategories' => $roomItem->withTags(), 'modifierList' => $modifierList]);
    }

    #[Route(path: '/room/{roomId}/{itemId}/send')]
    public function send(
        Request $request,
        ItemService $itemService,
        MailAssistant $mailAssistant,
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        int $roomId,
        int $itemId
    ): Response {
        // get item
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        // prepare form
        $groupChoices = $mailAssistant->getGroupChoices($item);
        $defaultGroupId = null;
        if ((is_countable($groupChoices) ? count($groupChoices) : 0) > 0) {
            $defaultGroupId = array_values($groupChoices)[0];
        }

        $isShowGroupAllRecipients = $mailAssistant->showGroupAllRecipients($request);

        $formData = new Send();
        $formData->setAdditionalRecipients(['']);
        $formData->setSendToGroups([$defaultGroupId]);
        if ($isShowGroupAllRecipients) {
            $formData->setSendToGroupAll(false);
        } else {
            $formData->setSendToGroupAll(null);
        }
        $formData->setSendToAll(false);
        $formData->setMessage($mailAssistant->prepareMessage($item));
        $formData->setSendToCreator(false);
        $formData->setCopyToSender(false);

        $form = $this->createForm(SendType::class, $formData, [
            'item' => $item,
            'uploadUrl' => $this->generateUrl('app_upload_mailattachments', [
                'roomId' => $roomId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // if cancel was clicked, redirect back to detail page
            if ($form->get('cancel')->isClicked()) {
                $itemType = $item->getType();
                if ('label' === $item->getType()) {
                    $itemType = $item->getLabelType();
                }

                return $this->redirectToRoute('app_'.$itemType.'_detail', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]);
            }

            // send mail
            $recipientCount = $mailAssistant->handleItemSendMessage($form, $item, $portalItem->getTitle());
            $this->addFlash('recipientCount', $recipientCount);

            // redirect to success page
            return $this->redirectToRoute('app_item_sendsuccess', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return $this->render('item/send.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/{itemId}/send/success')]
    public function sendSuccess(
        ItemService $itemService,
        int $roomId, int $itemId
    ): Response {
        // get item
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        $itemType = $item->getType();
        if ('label' == $item->getType()) {
            $itemType = $item->getLabelType();
        }

        return $this->render('item/send_success.html.twig', [
            'link' => $this->generateUrl('app_'.$itemType.'_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
            'title' => $item->getTitle(),
        ]);
    }

    /**
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/item/{itemId}/autocomplete/{feedAmount}', defaults: ['feedAmount' => 20])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function autocomplete(
        RoomService $roomService,
        ItemService $itemService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        $feedAmount
    ): Response {
        $environment = $legacyEnvironment->getEnvironment();

        $optionsData = [];
        $items = [];

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
                $optionsData['itemsLatest'][] = ['title' => $tempTypedItem->getTitle(), 'text' => $tempTypedItem->getType(), 'url' => '', 'id' => $tempTypedItem->getItemId()];
            }
            $latestItem = $latestItemList->getNext();
        }
        if (empty($optionsData['itemsLatest'])) {
            $optionsData['itemsLatest'] = [];
        }

        return new JsonResponse([
            $optionsData['itemsLatest'],
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/filelist')]
    public function filelist($roomId, $itemId, ItemService $itemService): JsonResponse
    {
        /** @var cs_item $item */
        $item = $itemService->getItem($itemId);

        /** @var cs_file_item[] $files */
        $files = $item->getFileList()->to_array();
        $fileArray = [];

        foreach ($files as $key => $file) {
            $fileArray[] = ['name' => $file->getFileName(), 'path' => $this->generateUrl('app_file_getfile', [
                'fileId' => $file->getFileID(),
            ], UrlGeneratorInterface::ABSOLUTE_PATH), 'id' => $file->getFileID(), 'ext' => $file->getExtension()];
        }

        return new JsonResponse([
            'files' => $fileArray,
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/stepper')]
    public function stepper($roomId, $itemId, ItemService $itemService, LegacyEnvironment $legacyEnvironment): Response
    {
        $environment = $legacyEnvironment->getEnvironment();

        $baseItem = $itemService->getItem($itemId);

        /** @var cs_manager $rubricManager */
        $rubricManager = $environment->getManager($baseItem->getItemType());

        /** @var cs_item $item */
        $item = $rubricManager->getItem($itemId);

        if ('project' == $baseItem->getItemType()) {
            $rubricManager->setCommunityroomLimit($roomId);
            $rubricManager->setContextLimit($environment->getCurrentPortalID());
        } else {
            $rubricManager->setContextLimit($roomId);
        }

        if ('date' == $item->getItemType()) {
            $rubricManager->setWithoutDateModeLimit();
        }

        if ($rubricManager instanceof cs_labels_manager && $item instanceof cs_label_item) {
            $rubricManager->setTypeLimit($item->getLabelType());
        }

        if (!$environment->getCurrentUserItem()->isModerator()) {
            $rubricManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        }

        $rubricManager->select();
        $itemList = $rubricManager->get();
        $items = $itemList->to_array();
        $itemList = [];
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
                    ++$counterBefore;
                }
                $itemList[] = $tempItem;
                if ($tempItem->getItemID() == $item->getItemID()) {
                    $foundItem = true;
                }
                if (!$foundItem) {
                    $prevItemId = $tempItem->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $itemList[] = $tempItem;
                    ++$counterAfter;
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
                $lastItemId = $items[sizeof($items) - 1]->getItemId();
            }
        }

        return $this->render('item/stepper.html.twig', [
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
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/get', condition: 'request.isXmlHttpRequest()')]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function singleArticle(
        ItemService $itemService,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('no item found for id '.$itemId);
        }

        return $this->render('item/single_article.html.twig', [
            'item' => $item,
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/links')]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function links(
        RoomService $roomService,
        ItemService $itemService,
        CategoryService $categoryService,
        LegacyEnvironment $environment,
        LabelService $labelService,
        int $roomId,
        int $itemId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $item = $itemService->getItem($itemId);

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $itemCategories = $item->getTagsArray();
            $categories = $labelService->getTagDetailArray($roomCategories, $itemCategories);
        }

        $roomItem = $roomService->getRoomItem($roomId);

        return $this->render('item/links.html.twig', [
            'item' => $item,
            'showHashtags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'roomCategories' => $categories,
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/canceledit')]
    public function cancelEdit(
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        int $roomId,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::CANCEL);

        // cancel editing a NEW entry => return to list view
        // cancel editing an EXISTING entry => return to detail view of the entry
        $redirectUrl = $this->generateUrl("app_{$item->getType()}_" . ($item->isDraft() ? 'list' : 'detail'), [
            'roomId' => $roomId,
            'itemId' => $item->getItemID(),
        ]);

        return $this->json([
            'redirectUrl' => $redirectUrl,
        ]);
    }

    #[Route(path: '/room/{roomId}/item/{itemId}/undraft', condition: 'request.isXmlHttpRequest()')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function undraft(
        ItemService $itemService,
        int $roomId,
        int $itemId
    ): Response {
        $item = $itemService->getItem($itemId);
        $item->setDraftStatus(0);
        $item->saveAsItem();

        return new Response();
    }
}
