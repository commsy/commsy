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

use App\Action\Activate\ActivateAction;
use App\Action\Activate\DeactivateAction;
use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\MarkRead\MarkReadAction;
use App\Action\Pin\PinAction;
use App\Action\Pin\UnpinAction;
use App\Event\CommsyEditEvent;
use App\Filter\TopicFilterType;
use App\Form\DataTransformer\TopicTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\TopicPathType;
use App\Form\Type\TopicType;
use App\Security\Authorization\Voter\CategoryVoter;
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\TopicService;
use cs_room_item;
use cs_topic_item;
use cs_user_item;
use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class TopicController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
#[IsGranted('RUBRIC_TOPIC')]
class TopicController extends BaseController
{
    private TopicService $topicService;

    /**
     * @param mixed $topicService
     */
    #[Required]
    public function setTopicService(TopicService $topicService): void
    {
        $this->topicService = $topicService;
    }

    #[Route(path: '/room/{roomId}/topic')]
    public function list(
        Request $request,
        int $roomId,
        ItemService $itemService
    ): Response {
        $roomItem = $this->getRoom($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        $filterForm = $this->createFilterForm($roomItem);
        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in topic manager
            $this->topicService->setFilterConditions($filterForm);
        } else {
            $this->topicService->hideDeactivatedEntries();
        }

        // get topic list from manager service
        $itemsCountArray = $this->topicService->getCountArray($roomId);

        $pinnedItems = $itemService->getPinnedItems($roomId, [ CS_TOPIC_TYPE, CS_LABEL_TYPE ]);

        $usageInfo = false;
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('topic')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('topic');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('topic');
        }

        return $this->render('topic/list.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm,
            'module' => CS_TOPIC_TYPE,
            'relatedModule' => CS_LABEL_TYPE,
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => false,
            'showCategories' => $roomItem->withTags(),
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->getArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'pinnedItemsCount' => count($pinnedItems)
        ]);
    }

    #[Route(path: '/room/{roomId}/topic/feed/{start}/{sort}')]
    public function feed(
        Request $request,
        ItemService $itemService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ): Response {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $topicFilter = $request->get('topicFilter');
        if (!$topicFilter) {
            $topicFilter = $request->query->all('topic_filter');
        }

        $roomItem = $this->getRoom($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($topicFilter) {
            $filterForm = $this->createFilterForm($roomItem);
            // manually bind values from the request
            $filterForm->submit($topicFilter);
            // set filter conditions in topic manager
            $this->topicService->setFilterConditions($filterForm);
        } else {
            $this->topicService->hideDeactivatedEntries();
        }

        // get topic list from manager service
        $topics = $this->topicService->getListTopics($roomId, $max, $start);

        $readerList = $this->readerService->getChangeStatusForItems(...$topics);

        $allowedActions = $itemService->getAllowedActionsForItems($topics);

        return $this->render('topic/feed.html.twig', ['roomId' => $roomId, 'topics' => $topics, 'readerList' => $readerList, 'showRating' => false, 'allowedActions' => $allowedActions]);
    }

    #[Route(path: '/room/{roomId}/topic/{itemId}', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function detail(
        Request $request,
        CategoryService $categoryService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ): Response {
        $current_context = $this->legacyEnvironment->getCurrentContextItem();
        $topic = $this->topicService->getTopic($itemId);
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
            $topicCategories = $topic->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $topicCategories);
        }

        $alert = null;
        if (!$this->isGranted(ItemVoter::EDIT_LOCK, $itemId)) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $this->topicService->getTopic($request->query->get('path'));
        }

        $isLinkedToItems = false;
        if (!empty($topic->getAllLinkedItemIDArray())) {
            $isLinkedToItems = true;
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        return $this->render('topic/detail.html.twig', [
            'roomId' => $roomId,
            'topic' => $infoArray['topic'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'topicList' => $infoArray['topicList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'pinned' => $infoArray['pinned'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'showHashtags' => $infoArray['showHashtags'],
            'language' => $infoArray['language'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $categories,
            'user' => $infoArray['user'],
            'annotationForm' => $form,
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
            'isLinkedToItems' => $isLinkedToItems
        ]);
    }

    private function getDetailInfo(
        int $roomId,
        int $itemId
    ) {
        $infoArray = [];
        $topic = $this->topicService->getTopic($itemId);

        $item = $topic;
        $this->readerService->markItemAsRead($item);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readCountDescription = $this->readerService->getReadCountDescriptionForItem($topic);

        $readerList = [];
        $modifierList = [];

        $readerList[$topic->getItemId()] = $this->readerService->getStatusForItem($topic)->value;
        $modifierList[$topic->getItemId()] = $this->itemService->getAdditionalEditorsForItem($topic);

        $topics = $this->topicService->getListTopics($roomId);
        $topicList = [];
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundTopic = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($topics as $tempTopic) {
            if (!$foundTopic) {
                if ($counterBefore > 5) {
                    array_shift($topicList);
                } else {
                    ++$counterBefore;
                }
                $topicList[] = $tempTopic;
                if ($tempTopic->getItemID() == $topic->getItemID()) {
                    $foundTopic = true;
                }
                if (!$foundTopic) {
                    $prevItemId = $tempTopic->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $topicList[] = $tempTopic;
                    ++$counterAfter;
                    if (!$nextItemId) {
                        $nextItemId = $tempTopic->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($topics)) {
            if ($prevItemId) {
                $firstItemId = $topics[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $topics[sizeof($topics) - 1]->getItemId();
            }
        }

        $infoArray['topic'] = $topic;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['topicList'] = $topicList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($topics);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $readCountDescription->getReadTotal();
        $infoArray['readSinceModificationCount'] = $readCountDescription->getReadSinceModification();
        $infoArray['userCount'] = $readCountDescription->getUserTotal();
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['pinned'] = $this->itemService->getItem($itemId)->isPinned();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['language'] = $this->legacyEnvironment->getCurrentContextItem()->getLanguage();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();

        return $infoArray;
    }

    #[Route(path: '/room/{roomId}/topic/create')]
    #[IsGranted('ITEM_NEW')]
    public function create(
        int $roomId
    ): RedirectResponse {
        // create new topic item
        $topicItem = $this->topicService->getNewtopic();
        $topicItem->setDraftStatus(1);
        $topicItem->save();

        return $this->redirectToRoute('app_topic_detail',
            ['roomId' => $roomId, 'itemId' => $topicItem->getItemId()]);
    }

    #[Route(path: '/room/{roomId}/topic/{itemId}/edit')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function edit(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        TopicTransformer $transformer,
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);
        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $isDraft = $item->isDraft();

        // get date from DateService
        $topicItem = $this->topicService->getTopic($itemId);
        if (!$topicItem) {
            throw $this->createNotFoundException('No topic found for id '.$itemId);
        }
        $formData = $transformer->transform($topicItem);
        $formData['category_mapping']['categories'] = $labelService->getLinkedCategoryIds($item);
        $formData['hashtag_mapping']['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
        $formData['language'] = $this->legacyEnvironment->getCurrentContextItem()->getLanguage();
        $formData['draft'] = $isDraft;
        $form = $this->createForm(TopicType::class, $formData, ['action' => $this->generateUrl('app_date_edit', ['roomId' => $roomId, 'itemId' => $itemId]), 'placeholderText' => '['.$this->translator->trans('insert title').']', 'categoryMappingOptions' => [
            'categories' => $labelService->getCategories($roomId),
            'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
            'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
        ], 'hashtagMappingOptions' => [
            'hashtags' => $labelService->getHashtags($roomId),
            'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
            'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
        ], 'room' => $current_context,
           'itemId' => $itemId]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $topicItem = $transformer->applyTransformation($topicItem, $form->getData());

                // update modifier
                $topicItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($form->has('category_mapping')) {
                    $categoryIds = $formData['category_mapping']['categories'] ?? [];

                    if (isset($formData['category_mapping']['newCategory']) && $this->isGranted(CategoryVoter::EDIT)) {
                        $newCategoryTitle = $formData['category_mapping']['newCategory'];
                        $newCategory = $categoryService->addTag($newCategoryTitle, $roomId);
                        $categoryIds[] = $newCategory->getItemID();
                    }

                    if (!empty($categoryIds)) {
                        $topicItem->setTagListByID($categoryIds);
                    }
                }
                if ($form->has('hashtag_mapping')) {
                    $hashtagIds = $formData['hashtag_mapping']['hashtags'] ?? [];

                    if (isset($formData['hashtag_mapping']['newHashtag'])) {
                        $newHashtagTitle = $formData['hashtag_mapping']['newHashtag'];
                        $newHashtag = $labelService->getNewHashtag($newHashtagTitle, $roomId);
                        $hashtagIds[] = $newHashtag->getItemID();
                    }

                    if (!empty($hashtagIds)) {
                        $topicItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $topicItem->save();
            }

            return $this->redirectToRoute('app_topic_save', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($topicItem), CommsyEditEvent::EDIT);

        return $this->render('topic/edit.html.twig', ['form' => $form, 'topic' => $topicItem, 'isDraft' => $isDraft, 'language' => $this->legacyEnvironment->getCurrentContextItem()->getLanguage()]);
    }

    #[Route(path: '/room/{roomId}/topic/{itemId}/save')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function save(
        int $roomId,
        int $itemId
    ): Response {
        $topic = $this->topicService->getTopic($itemId);

        $itemArray = [$topic];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $readCountDescription = $this->readerService->getReadCountDescriptionForItem($topic);

        $readerList = [];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getStatusForItem($item)->value;

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($topic), CommsyEditEvent::SAVE);

        return $this->render('topic/save.html.twig', [
            'roomId' => $roomId,
            'item' => $topic,
            'modifierList' => $modifierList,
            'userCount' => $readCountDescription->getUserTotal(),
            'readCount' => $readCountDescription->getReadTotal(),
            'readSinceModificationCount' => $readCountDescription->getReadSinceModification(),
        ]);
    }

    #[Route(path: '/room/{roomId}/topic/{itemId}/print')]
    public function print(
        PrintService $printService,
        int $roomId,
        int $itemId
    ): Response {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('topic/detail_print.html.twig', [
            'roomId' => $roomId,
            'item' => $infoArray['topic'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'roomCategories' => 'roomCategories',
        ]);

        return $printService->buildPdfResponse($html);
    }

    #[Route(path: '/room/{roomId}/topic/print/{sort}', defaults: ['sort' => 'none'])]
    public function printlist(
        Request $request,
        AssessmentService $assessmentService,
        PrintService $printService,
        int $roomId
    ): Response {
        $roomItem = $this->getRoom($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        $filterForm = $this->createFilterForm($roomItem);
        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in announcement manager
            $this->topicService->setFilterConditions($filterForm);
        }
        // get announcement list from manager service
        $topics = $this->topicService->getListTopics($roomId);
        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = $this->readerService->getChangeStatusForItems(...$topics);

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
            foreach ($topics as $topic) {
                $itemIds[] = $topic->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get announcement list from manager service
        $itemsCountArray = $this->topicService->getCountArray($roomId);

        $html = $this->renderView('topic/list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'topic',
            'announcements' => $topics,
            'readerList' => $readerList,
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->withAssociations(),
            'showCategories' => $roomItem->withTags(),
            'ratingList' => $ratingList,
            'showWorkflow' => $current_context->withWorkflow(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    #[Route(path: '/room/{roomId}/topic/{itemId}/editpath')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editPath(
        Request $request,
        int $roomId,
        int $itemId
    ): Response {
        /** @var cs_topic_item $item */
        $item = $this->itemService->getTypedItem($itemId);

        $formData = [];

        $pathElements = [];
        $pathElementsAttr = [];

        $itemManager = $this->legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextLimit($roomId);

        // get all linked items
        $linkedItemArray = [];
        foreach ($item->getPathItemList()->to_array() as $pathElement) {
            $formData['path'][] = $pathElement->getItemId();
            $linkedItemArray[] = $pathElement;
        }
        foreach ($itemManager->getItemList($item->getAllLinkedItemIDArray())->to_array() as $linkedItem) {
            $inPath = false;
            foreach ($linkedItemArray as $linkedItemPath) {
                if ($linkedItemPath->getItemId() == $linkedItem->getItemId()) {
                    $inPath = true;
                    break;
                }
            }
            if (!$inPath) {
                $linkedItemArray[] = $linkedItem;
            }
        }

        foreach ($linkedItemArray as $linkedItem) {
            $typedLinkedItem = $this->itemService->getTypedItem($linkedItem->getItemId());
            $pathElements[$typedLinkedItem->getTitle()] = $typedLinkedItem->getItemId();
            $pathElementsAttr[$typedLinkedItem->getTitle()] = [
                'title' => $typedLinkedItem->getTitle(),
                'type' => $typedLinkedItem->getItemType(),
            ];
        }

        $form = $this->createForm(TopicPathType::class, $formData, ['action' => $this->generateUrl('app_topic_editpath', ['roomId' => $roomId, 'itemId' => $itemId]), 'pathElements' => $pathElements, 'pathElementsAttr' => $pathElementsAttr]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $linkManager = $this->legacyEnvironment->getLinkItemManager();

                $formData = $form->getData();

                $formDataPath = [];
                if (isset($formData['path'])) {
                    $formDataPath = $formData['path'];
                }
                if (!empty($formDataPath)) {
                    $sortingPlace = 1;
                    if (isset($formData['pathOrder'])) {
                        foreach (explode(',', (string) $formData['pathOrder']) as $orderItemId) {
                            if ($linkItem = $linkManager->getItemByFirstAndSecondID($item->getItemId(), $orderItemId,
                                true)) {
                                if (in_array($orderItemId, $formDataPath)) {
                                    $linkItem->setSortingPlace($sortingPlace);
                                    $linkItem->save();
                                    ++$sortingPlace;
                                }
                            }
                        }
                    }
                    $item->activatePath();
                    $item->save();
                } else {
                    $item->deactivatePath();
                    $item->save();
                }

                if (isset($formData['pathOrder'])) {
                    foreach (explode(',', (string) $formData['pathOrder']) as $orderItemId) {
                        if ($linkItem = $linkManager->getItemByFirstAndSecondID($item->getItemId(), $orderItemId)) {
                            if (!in_array($orderItemId, $formDataPath)) {
                                $linkManager->cleanSortingPlaces($this->itemService->getTypedItem($orderItemId));
                            }
                        }
                    }
                }
            } else {
                if ($form->get('cancel')->isClicked()) {
                    // ToDo ...
                }
            }

            return $this->redirectToRoute('app_topic_savepath', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        $this->eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        return $this->render('topic/edit_path.html.twig', ['form' => $form]);
    }

    #[Route(path: '/room/{roomId}/topic/{itemId}/savepath')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function savePath(
        // Do not remove $roomId even if it is unused, @IsGranted() relies on this argument
        /* @noinspection PhpUnusedParameterInspection */
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);
        $this->eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);
        $isLinkedToItems = false;
        if (!empty($item->getAllLinkedItemIDArray())) {
            $isLinkedToItems = true;
        }

        return $this->render('topic/save_path.html.twig', [
            'topic' => $this->itemService->getTypedItem($itemId),
            'isLinkedToItems' => $isLinkedToItems,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/download')]
    public function download(
        Request $request,
        DownloadAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkRead(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/pin', condition: 'request.isXmlHttpRequest()')]
    public function xhrPinAction(
        Request $request,
        PinAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/unpin', condition: 'request.isXmlHttpRequest()')]
    public function xhrUnpinAction(
        Request $request,
        UnpinAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorize(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtag(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/activate', condition: 'request.isXmlHttpRequest()')]
    public function xhrActivate(
        Request $request,
        ActivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/deactivate', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeactivate(
        Request $request,
        DeactivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/topic/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDelete(
        Request $request,
        DeleteAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_topic_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        if ($selectAll) {
            if ($request->query->has('topic_filter')) {
                $currentFilter = $request->query->all('topic_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->topicService->setFilterConditions($filterForm);
            } else {
                $this->topicService->hideDeactivatedEntries();
            }

            return $this->topicService->getListTopics($roomItem->getItemID());
        } else {
            return $this->topicService->getTopicsById($roomItem->getItemID(), $itemIds);
        }
    }

    /**
     * @param cs_room_item $room
     *
     * @return FormInterface
     */
    private function createFilterForm(
        $room
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(TopicFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_topic_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    private function getTagDetailArray(
        $baseCategories,
        $itemCategories
    ) {
        $result = [];
        $tempResult = [];
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                    } else {
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']];
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                }
            }
            $tempResult = [];
            $addCategory = false;
        }

        return $result;
    }
}
