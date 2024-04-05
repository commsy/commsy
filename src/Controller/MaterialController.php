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
use App\Action\Delete\DeleteMaterial;
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\Mark\MarkAction;
use App\Action\MarkRead\MarkReadAction;
use App\Action\MarkRead\MarkReadMaterial;
use App\Action\Pin\PinAction;
use App\Action\Pin\UnpinAction;
use App\Event\CommsyEditEvent;
use App\Filter\MaterialFilterType;
use App\Form\DataTransformer\MaterialTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\MaterialSectionType;
use App\Form\Type\MaterialType;
use App\Form\Type\SectionType;
use App\Http\JsonRedirectResponse;
use App\Repository\LicenseRepository;
use App\Security\Authorization\Voter\CategoryVoter;
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\MaterialService;
use App\Utils\TopicService;
use cs_material_item;
use cs_room_item;
use DateTime;
use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class MaterialController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
#[IsGranted('RUBRIC_MATERIAL')]
class MaterialController extends BaseController
{
    private MaterialService $materialService;

    private AnnotationService $annotationService;

    private CategoryService $categoryService;

    private MaterialTransformer $materialTransformer;

    private AssessmentService $assessmentService;

    #[Required]
    public function setCategoryService(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * @param mixed $materialService
     */
    #[Required]
    public function setMaterialService(MaterialService $materialService): void
    {
        $this->materialService = $materialService;
    }

    /**
     * @param mixed $annotationService
     */
    #[Required]
    public function setAnnotationService(AnnotationService $annotationService): void
    {
        $this->annotationService = $annotationService;
    }

    #[Required]
    public function setMaterialTransformer(MaterialTransformer $materialTransformer)
    {
        $this->materialTransformer = $materialTransformer;
    }

    #[Required]
    public function setAssessmentService(AssessmentService $assessmentService): void
    {
        $this->assessmentService = $assessmentService;
    }

    #[Route(path: '/room/{roomId}/material/feed/{start}/{sort}')]
    public function feed(
        Request $request,
        ItemService $itemService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $materialFilter = $request->get('materialFilter');
        if (!$materialFilter) {
            $materialFilter = $request->query->all('material_filter');
        }

        $roomItem = $this->getRoom($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($materialFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            // manually bind values from the request
            $filterForm->submit($materialFilter);

            // set filter conditions in material manager
            $this->materialService->setFilterConditions($filterForm);
        } else {
            $this->materialService->hideDeactivatedEntries();
        }

        if (empty($sort)) {
            $sort = $request->getSession()->get('sortMaterials', 'date');
        }
        $request->getSession()->set('sortMaterials', $sort);

        // get material list from manager service
        $materials = $this->materialService->getListMaterials($roomId, $max, $start, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $allowedActions = $itemService->getAllowedActionsForItems($materials);

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
            foreach ($materials as $material) {
                $itemIds[] = $material->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
        }

        return $this->render('material/feed.html.twig', [
            'roomId' => $roomId,
            'materials' => $materials,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'withTrafficLight' => $roomItem->withWorkflowTrafficLight(),
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ]
        ]);
    }

    #[Route(path: '/room/{roomId}/material')]
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
            // set filter conditions in material manager
            $this->materialService->setFilterConditions($filterForm);
        } else {
            $this->materialService->hideDeactivatedEntries();
        }

        $sort = $request->getSession()->get('sortMaterials', 'date');

        // get material list from manager service
        $itemsCountArray = $this->materialService->getCountArray($roomId);

        $pinnedItems = $itemService->getPinnedItems($roomId, [ CS_MATERIAL_TYPE, CS_SECTION_TYPE ]);

        $usageInfo = false;
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('material')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('material');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('material');
        }

        return $this->render('material/list.html.twig', [
            'roomId' => $roomId,
            'form' => $filterForm,
            'module' => CS_MATERIAL_TYPE,
            'relatedModule' => CS_SECTION_TYPE,
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showAssociations' => $roomItem->withAssociations(),
            'showWorkflow' => $roomItem->withWorkflow(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'material_filter' => $filterForm,
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->getArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'isMaterialOpenForGuests' => $roomItem->isMaterialOpenForGuests(),
            'sort' => $sort,
            'pinnedItemsCount' => count($pinnedItems)
        ]);
    }

    #[Route(path: '/room/{roomId}/material/print/{sort}', defaults: ['sort' => 'none'])]
    public function printlist(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ): Response {
        $roomItem = $this->getRoom($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createFilterForm($roomItem);

        $numAllMaterials = $this->materialService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $this->materialService->setFilterConditions($filterForm);
        }

        // get material list from manager service
        if ('none' === $sort || empty($sort)) {
            $sort = $request->getSession()->get('sortMaterials', 'date');
        }
        $materials = $this->materialService->getListMaterials($roomId, $numAllMaterials, 0, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
            foreach ($materials as $material) {
                $itemIds[] = $material->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
        }

        // get material list from manager service
        $itemsCountArray = $this->materialService->getCountArray($roomId);

        $html = $this->renderView('material/list_print.html.twig', [
            'roomId' => $roomId,
            'module' => 'material',
            'materials' => $materials,
            'itemsCountArray' => $itemsCountArray,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
        ]);

        return $printService->buildPdfResponse($html);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/{versionId}', requirements: ['itemId' => '\d+', 'versionId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function detail(
        Request $request,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId,
        int $versionId = null
    ): Response {
        $roomItem = $this->getRoom($roomId);
        $infoArray = $this->getDetailInfo($roomId, $itemId, $versionId);

        $canExportToWordpress = false;

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if (!$this->isGranted(ItemVoter::EDIT_LOCK, $itemId)) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        $amountAnnotations = $this->annotationService->getListAnnotations($roomId, $infoArray['material']->getItemId(),
            null, null);

        return $this->render('material/detail.html.twig', [
            'roomId' => $roomId,
            'material' => $infoArray['material'],
            'amountAnnotations' => sizeof($amountAnnotations),
            'sectionList' => $infoArray['sectionList'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'materialList' => $infoArray['materialList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'workflowGroupArray' => $infoArray['workflowGroupArray'],
            'workflowUserArray' => $infoArray['workflowUserArray'],
            'workflowText' => $infoArray['workflowText'],
            'workflowValidityDate' => $infoArray['workflowValidityDate'],
            'workflowResubmissionDate' => $infoArray['workflowResubmissionDate'],
            'workflowUnread' => $infoArray['workflowUnread'],
            'workflowRead' => $infoArray['workflowRead'],
            'draft' => $infoArray['draft'],
            'pinned' => $infoArray['pinned'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'withTrafficLight' => $roomItem->withWorkflowTrafficLight(),
            'withResubmission' => $roomItem->withWorkflowResubmission(),
            'withValidity' => $roomItem->withWorkflowValidity(),
            'withReader' => $roomItem->withWorkflowReader(),
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'user' => $infoArray['user'],
            'annotationForm' => $form,
            'ratingArray' => $infoArray['ratingArray'],
            'canExportToWordpress' => $canExportToWordpress,
            'roomCategories' => $infoArray['roomCategories'],
            'versions' => $infoArray['versions'],
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem
        ]);
    }

    /**
     * @return JsonRedirectResponse
     *
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/material/{itemId}/workflow', condition: 'request.isXmlHttpRequest()')]
    public function workflow(
        Request $request,
        int $roomId,
        int $itemId
    ): Response {
        if ($request->request->has('payload')) {
            $payload = $request->request->all('payload');

            if (isset($payload['read']) && $payload['read']) {
                $read = $payload['read'];

                $itemManager = $this->legacyEnvironment->getItemManager();
                $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
                $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

                if ($currentContextItem->withWorkflow()) {
                    if ('true' == $read) {
                        $itemManager->markItemAsWorkflowRead($itemId, $currentUserItem->getItemID());
                    } else {
                        $itemManager->markItemAsWorkflowNotRead($itemId, $currentUserItem->getItemID());
                    }
                } else {
                    throw new Exception('workflow is not enabled');
                }
            }
        }

        return new JsonRedirectResponse($this->generateUrl('app_material_detail', [
            'roomId' => $roomId,
            'itemId' => $itemId,
        ]));
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/rating/{vote}')]
    public function rating(
        int $roomId,
        int $itemId,
        string $vote
    ): Response {
        $material = $this->materialService->getMaterial($itemId);
        if ('remove' != $vote) {
            $this->assessmentService->rateItem($material, $vote);
        } else {
            $this->assessmentService->removeRating($material);
        }
        $ratingDetail = $this->assessmentService->getRatingDetail($material);
        $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($material);
        $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($material);

        return $this->render('material/rating.html.twig', ['roomId' => $roomId, 'material' => $material, 'ratingArray' => ['ratingDetail' => $ratingDetail, 'ratingAverageDetail' => $ratingAverageDetail, 'ratingOwnDetail' => $ratingOwnDetail]]);
    }

    private function getDetailInfo(
        int $roomId,
        int $itemId,
        int $versionId = null
    ) {
        $infoArray = [];

        /** @var cs_material_item $material */
        $material = null;
        if (null === $versionId) {
            $material = $this->materialService->getMaterial($itemId);
        } else {
            $material = $this->materialService->getMaterialByVersion($itemId, $versionId);
        }

        if (null == $material) {
            $section = $this->materialService->getSection($itemId);
            $material = $this->materialService->getMaterial($section->getLinkedItemID());
        }

        $sectionList = $material->getSectionList()->to_array();

        $itemArray = [$material];
        $itemArray = array_merge($itemArray, $sectionList);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerManager = $this->legacyEnvironment->getReaderManager();

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $id_array = $user_list->getIDArray();
        $readerManager->getLatestReaderByUserIDArray($id_array, $material->getItemID());

        foreach ($user_list as $current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($material->getItemID(), $current_user->getItemID());
            if (!empty($current_reader)) {
                ++$read_count;

                if ($current_reader['read_date'] >= $material->getModificationDate()) {
                    ++$read_since_modification_count;
                }
            }
        }

        $readerList = [];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $materials = $this->materialService->getListMaterials($roomId);
        $materialList = [];
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundMaterial = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($materials as $tempMaterial) {
            if (!$foundMaterial) {
                if ($counterBefore > 5) {
                    array_shift($materialList);
                } else {
                    ++$counterBefore;
                }
                $materialList[] = $tempMaterial;
                if ($tempMaterial->getItemID() == $material->getItemID()) {
                    $foundMaterial = true;
                }
                if (!$foundMaterial) {
                    $prevItemId = $tempMaterial->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $materialList[] = $tempMaterial;
                    ++$counterAfter;
                    if (!$nextItemId) {
                        $nextItemId = $tempMaterial->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($materials)) {
            if ($prevItemId) {
                $firstItemId = $materials[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $materials[sizeof($materials) - 1]->getItemId();
            }
        }

        // workflow
        $workflowGroupArray = [];
        $workflowUserArray = [];
        $workflowRead = false;
        $workflowUnread = false;

        if ($current_context->withWorkflowReader()) {
            $itemManager = $this->legacyEnvironment->getItemManager();
            $users_read_array = $itemManager->getUsersMarkedAsWorkflowReadForItem($material->getItemID());
            $persons_array = [];
            foreach ($users_read_array as $user_read) {
                $persons_array[] = $userManager->getItem($user_read['user_id']);
            }

            if ('1' == $current_context->getWorkflowReaderGroup()) {
                $group_manager = $this->legacyEnvironment->getGroupManager();
                $group_manager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
                $group_manager->setTypeLimit('group');
                $group_manager->select();
                $group_list = $group_manager->get();
                $group_item = $group_list->getFirst();
                while ($group_item) {
                    $link_user_list = $group_item->getLinkItemList('user');
                    $user_count_complete = $link_user_list->getCount();
                    $user_count = 0;
                    foreach ($persons_array as $person) {
                        if (!empty($persons_array[0])) {
                            $temp_link_list = $person->getLinkItemList('group');
                            $temp_link_item = $temp_link_list->getFirst();

                            while ($temp_link_item) {
                                $temp_group_item = $temp_link_item->getLinkedItem($person);
                                if ($group_item->getItemID() == $temp_group_item->getItemID()) {
                                    ++$user_count;
                                }
                                $temp_link_item = $temp_link_list->getNext();
                            }
                        }
                    }
                    $tmpArray = [];
                    $tmpArray['iid'] = $group_item->getItemID();
                    $tmpArray['title'] = $group_item->getTitle();
                    $tmpArray['userCount'] = $user_count;
                    $tmpArray['userCountComplete'] = $user_count_complete;
                    $workflowGroupArray[] = $tmpArray;
                    $group_item = $group_list->getNext();
                }
            }

            if ('1' == $current_context->getWorkflowReaderPerson()) {
                foreach ($persons_array as $person) {
                    if (!empty($persons_array[0])) {
                        $tmpArray = [];
                        $tmpArray['iid'] = $person->getItemID();
                        $tmpArray['name'] = $person->getFullname();
                        $workflowUserArray[] = $tmpArray;
                    }
                }
            }

            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
            $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

            if ($currentContextItem->withWorkflow()) {
                if (!$currentUserItem->isRoot()) {
                    if (!$currentUserItem->isGuest() && $material->isReadByUser($currentUserItem)) {
                        $workflowUnread = true;
                    } else {
                        $workflowRead = true;
                    }
                }
            }
        }

        $workflowText = '';
        if ($current_context->withWorkflow()) {
            $workflowText = match ($material->getWorkflowTrafficLight()) {
                '0_green' => $current_context->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $current_context->getWorkflowTrafficLightTextYellow(),
                '2_red' => $current_context->getWorkflowTrafficLightTextRed(),
                default => '',
            };
        }

        $ratingDetail = [];
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $this->assessmentService->getRatingDetail($material);
            $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($material);
            $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($material);
        }

        $reader_manager = $this->legacyEnvironment->getReaderManager();

        $item = $material;
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $readsectionList = $material->getSectionList();

        $section = $readsectionList->getFirst();
        while ($section) {
            $reader = $reader_manager->getLatestReader($section->getItemID());
            if (empty($reader) || $reader['read_date'] < $section->getModificationDate()) {
                $reader_manager->markRead($section->getItemID(), 0);
            }

            $section = $readsectionList->getNext();
        }

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $this->categoryService->getTags($roomId);
            $materialCategories = $material->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $materialCategories);
        }

        $versions = [];
        $versionList = $this->materialService->getVersionList($material->getItemId())->to_array();

        if ((is_countable($versionList) ? count($versionList) : 0) > 1) {
            $minTimestamp = time();
            $maxTimestamp = -1;
            $first = true;
            foreach ($versionList as $versionItem) {
                $tempParsedDate = date_parse($versionItem->getModificationDate());
                $tempDateTime = new DateTime();
                $tempDateTime->setDate($tempParsedDate['year'], $tempParsedDate['month'], $tempParsedDate['day']);
                $tempDateTime->setTime($tempParsedDate['hour'], $tempParsedDate['minute'], $tempParsedDate['second']);
                $tempTimeStamp = $tempDateTime->getTimeStamp();
                $current = false;
                if (null !== $versionId) {
                    if ($versionId == $versionItem->getVersionId()) {
                        $current = true;
                    }
                } else {
                    if ($first) {
                        $current = true;
                        $first = false;
                    }
                }
                $versions[$tempTimeStamp] = ['item' => $versionItem, 'date' => date('d.m.Y H:i', $tempTimeStamp), 'current' => $current];
                if ($tempTimeStamp > $maxTimestamp) {
                    $maxTimestamp = $tempTimeStamp;
                }
                if ($tempTimeStamp < $minTimestamp) {
                    $minTimestamp = $tempTimeStamp;
                }
            }
            asort($versions);

            $timeDiff = $maxTimestamp - $minTimestamp;
            $minPercentDiff = ($timeDiff / 100) * sizeof($versions);
            $lastPercent = 0;
            $first = true;
            $toFollow = sizeof($versions) - 1;
            foreach ($versions as $timestamp => $versionId) {
                $tempTimeDiff = $timestamp - $minTimestamp;
                $tempPercent = 0;
                if ($timeDiff > 0) {
                    $tempPercent = $tempTimeDiff / ($timeDiff / 100);
                }
                if (!$first) {
                    if (($tempPercent - $lastPercent) < 2) {
                        while (($tempPercent - $lastPercent) < 2 && ($tempPercent - $lastPercent) < $minPercentDiff) {
                            ++$tempPercent;
                        }
                    }
                } else {
                    $first = false;
                }

                if ($tempPercent >= 95) {
                    if (0 != $toFollow) {
                        $tempPercent = $tempPercent - ($toFollow * 2);
                    }
                }

                $versions[$timestamp]['percent'] = $tempPercent;
                $lastPercent = $tempPercent;
                --$toFollow;
            }
        }

        $infoArray['material'] = $material;
        $infoArray['sectionList'] = $sectionList;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['materialList'] = $materialList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($materials);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['workflowGroupArray'] = $workflowGroupArray;
        $infoArray['workflowUserArray'] = $workflowUserArray;
        $infoArray['workflowText'] = $workflowText;
        $infoArray['workflowValidityDate'] = $material->getWorkflowValidityDate();
        $infoArray['workflowResubmissionDate'] = $material->getWorkflowResubmissionDate();
        $infoArray['workflowUnread'] = $workflowUnread;
        $infoArray['workflowRead'] = $workflowRead;
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['pinned'] = $this->itemService->getItem($itemId)->isPinned();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();
        $infoArray['ratingArray'] = $current_context->isAssessmentActive() ? [
            'ratingDetail' => $ratingDetail,
            'ratingAverageDetail' => $ratingAverageDetail,
            'ratingOwnDetail' => $ratingOwnDetail,
        ] : [];
        $infoArray['roomCategories'] = $categories;
        $infoArray['versions'] = $versions;

        return $infoArray;
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
            $tempArray = [];
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

    #[Route(path: '/room/{roomId}/material/{itemId}/saveworkflow')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function saveWorkflow(
        int $roomId,
        int $itemId
    ): Response {
        $roomItem = $this->getRoom($roomId);
        $item = $this->itemService->getItem($itemId);
        $tempItem = null;

        if ('material' == $item->getItemType()) {
            $tempItem = $this->materialService->getMaterial($itemId);
        }

        $itemArray = [$tempItem];

        $modifierList = [];
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        return $this->render('material/save_workflow.html.twig', ['roomId' => $roomId, 'item' => $tempItem, 'modifierList' => $modifierList, 'workflowGroupArray' => $infoArray['workflowGroupArray'], 'workflowUserArray' => $infoArray['workflowUserArray'], 'workflowText' => $infoArray['workflowText'], 'workflowValidityDate' => $infoArray['workflowValidityDate'], 'workflowResubmissionDate' => $infoArray['workflowResubmissionDate'], 'workflowTitles' => [
            '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
            '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
            '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
            '3_none' => '',
        ]]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/edit')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function edit(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        LicenseRepository $licenseRepository,
        int $roomId,
        int $itemId
    ): Response {
        $form = null;
        // NOTE: this method currently gets used for both, material & section items
        // TODO: move handling of sections into a dedicated `editSectionAction()`
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $typedItem = null;
        $isMaterial = false;
        $isDraft = false;
        $isSaved = false;

        $licenses = [];
        $licensesContent = [];

        if ('material' == $item->getItemType()) {
            $isMaterial = true;
            if ($item->isDraft()) {
                $isDraft = true;
            }

            // get material from MaterialService
            $materialItem = $this->materialService->getMaterial($itemId);
            $typedItem = $materialItem;
            $materialItem->setDraftStatus($item->isDraft());
            if (!$materialItem) {
                throw $this->createNotFoundException('No material found for id '.$roomId);
            }

            $formData = $this->materialTransformer->transform($materialItem);
            $formData['category_mapping']['categories'] = $labelService->getLinkedCategoryIds($item);
            $formData['hashtag_mapping']['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);

            $availableLicenses = $licenseRepository->findByContextOrderByPosition($this->legacyEnvironment->getCurrentPortalId());
            foreach ($availableLicenses as $availableLicense) {
                $licenses[$availableLicense->getTitle()] = $availableLicense->getId();
                $licensesContent[$availableLicense->getId()] = $availableLicense->getContent();
            }

            $form = $this->createForm(MaterialType::class, $formData, [
                'action' => $this->generateUrl('app_material_edit', [
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ]),
                'placeholderText' => '['.$this->translator->trans('insert title').']',
                'categoryMappingOptions' => [
                    'categories' => $labelService->getCategories($roomId),
                    'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                    'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
                ], 'hashtagMappingOptions' => [
                    'hashtags' => $labelService->getHashtags($roomId),
                    'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                    'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
                ],
                'licenses' => $licenses,
                'room' => $current_context,
                'itemId' => $itemId
            ]);

            $this->eventDispatcher->dispatch(new CommsyEditEvent($materialItem), CommsyEditEvent::EDIT);
        } else {
            if ('section' == $item->getItemType()) {
                // get section from MaterialService
                $section = $this->materialService->getSection($itemId);
                $typedItem = $section;
                if (!$section) {
                    throw $this->createNotFoundException('No section found for id '.$roomId);
                }
                $formData = $this->materialTransformer->transform($section);
                $form = $this->createForm(SectionType::class, $formData, ['placeholderText' => '['.$this->translator->trans('insert title').']']);

                $this->eventDispatcher->dispatch(new CommsyEditEvent($this->materialService->getMaterial($section->getlinkedItemID())),
                    CommsyEditEvent::EDIT);
            }
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $typedItem = $this->materialTransformer->applyTransformation($typedItem, $form->getData());

                // update modifier
                $typedItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

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
                        $typedItem->setTagListByID($categoryIds);
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
                        $typedItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $typedItem->save();

                if (CS_SECTION_TYPE == $typedItem->getItemType()) {
                    $linkedMaterialItem = $this->materialService->getMaterial($typedItem->getlinkedItemID());
                    $linkedMaterialItem->save();
                }

                return $this->redirectToRoute('app_material_save', ['roomId' => $roomId, 'itemId' => $itemId]);
            }
        }

        return $this->render('material/edit.html.twig', [
            'isSaved' => $isSaved,
            'isDraft' => $isDraft,
            'isMaterial' => $isMaterial,
            'form' => $form,
            'material' => $typedItem,
            'licenses' => $licenses,
            'licensesContent' => $licensesContent],
        );
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/save')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function save(
        int $roomId,
        int $itemId
    ): Response {
        $roomItem = $this->getRoom($roomId);
        $item = $this->itemService->getItem($itemId);
        $tempItem = null;

        if ('material' == $item->getItemType()) {
            $tempItem = $this->materialService->getMaterial($itemId);

            $this->eventDispatcher->dispatch(new CommsyEditEvent($tempItem), CommsyEditEvent::SAVE);
        } else {
            if ('section' == $item->getItemType()) {
                $tempItem = $this->materialService->getSection($itemId);

                $this->eventDispatcher->dispatch(new CommsyEditEvent($this->materialService->getMaterial($tempItem->getLinkedItemID())),
                    CommsyEditEvent::SAVE);
            }
        }

        $itemArray = [$tempItem];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        return $this->render('material/save.html.twig', [
            'roomId' => $roomId,
            'item' => $tempItem,
            'modifierList' => $modifierList,
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'withTrafficLight' => $roomItem->withWorkflowTrafficLight(),
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ]
        ]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/print')]
    public function print(
        PrintService $printService,
        int $roomId,
        int $itemId
    ): Response {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $html = $this->renderView('material/detail_print.html.twig', [
            'roomId' => $roomId,
            'material' => $infoArray['material'],
            'sectionList' => $infoArray['sectionList'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'materialList' => $infoArray['materialList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'workflowGroupArray' => $infoArray['workflowGroupArray'],
            'workflowUserArray' => $infoArray['workflowUserArray'],
            'workflowText' => $infoArray['workflowText'],
            'workflowValidityDate' => $infoArray['workflowValidityDate'],
            'workflowResubmissionDate' => $infoArray['workflowResubmissionDate'],
            'workflowUnread' => $infoArray['workflowUnread'],
            'workflowRead' => $infoArray['workflowRead'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'user' => $infoArray['user'],
            'ratingArray' => $infoArray['ratingArray'],
            'roomCategories' => $infoArray['roomCategories'],
        ]);

        return $printService->buildPdfResponse($html);
    }

    #[Route(path: '/room/{roomId}/material/create')]
    #[IsGranted('ITEM_NEW')]
    public function create(
        int $roomId
    ): RedirectResponse {
        $roomItem = $this->getRoom($roomId);

        // create new material item
        $materialItem = $this->materialService->getNewMaterial();
        $materialItem->setBibKind('none');
        $materialItem->setDraftStatus(1);
        $materialItem->setPrivateEditing('1');
        if ($roomItem->withWorkflow()) {
            $materialItem->setWorkflowTrafficLight($roomItem->getWorkflowTrafficLightDefault());
        }
        $materialItem->save();

        return $this->redirectToRoute('app_material_detail', [
            'roomId' => $roomId,
            'itemId' => $materialItem->getItemId(),
        ]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/createsection')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function createSection(
        int $roomId,
        int $itemId
    ): Response {
        $material = $this->materialService->getMaterial($itemId);
        $sectionList = $material->getSectionList();
        $countSections = $sectionList->getCount();

        $section = $this->materialService->getNewSection();
        $section->setDraftStatus(1);
        $section->setLinkedItemId($itemId);
        $section->setVersionId($material->getVersionId());
        $section->setNumber($countSections + 1);
        $section->save();

        return $this->render('material/create_section.html.twig', [
            'sectionList' => $sectionList,
            'material' => $material,
            'section' => $section,
            'modifierList' => [],
        ]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/savesection')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function saveSection(
        Request $request,
        int $roomId,
        int $itemId
    ): RedirectResponse {
        $item = $this->itemService->getItem($itemId);

        // get section
        $section = $this->materialService->getSection($itemId);

        $formData = $this->materialTransformer->transform($section);

        $form = $this->createForm(SectionType::class, $formData, [
            'action' => $this->generateUrl('app_material_savesection', [
                'roomId' => $roomId,
                'itemId' => $section->getItemID(),
            ]),
            'placeholderText' => '['.$this->translator->trans('insert title').']',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // update title
                $section->setTitle($form->getData()['title']);

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }

                // update modifier
                $section->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                $section->save();

                $section->getLinkedItem()->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                // this will also update the material item's modification date to indicate that it has changes
                $section->getLinkedItem()->save();
            } else {
                if ($form->get('cancel')->isClicked()) {
                    // remove not saved item
                    $section->delete();

                    $section->save();
                }
            }
        }

        return $this->redirectToRoute('app_material_detail',
            ['roomId' => $roomId, 'itemId' => $section->getLinkedItemID()]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/sortsections')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function sortSections(
        Request $request,
        int $itemId
    ): Response {
        // get section
        $material = $this->materialService->getMaterial($itemId);

        $json = json_decode($request->getContent(), null, 512, JSON_THROW_ON_ERROR);

        $i = 1;
        foreach ($json as $key => $value) {
            // set sorting
            $section = $this->materialService->getSection($value[0]);
            $section->setNumber($i);
            $section->save();
            ++$i;
        }

        $sectionList = $material->getSectionList()->to_array();

        return $this->render('material/sort_sections.html.twig', ['sectionList' => $sectionList, 'material' => $material]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/editsections')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editSections(
        Request $request,
        int $roomId,
        int $itemId
    ): Response {
        $material = $this->materialService->getMaterial($itemId);
        $item = $this->itemService->getItem($itemId);

        if (!$material) {
            throw $this->createNotFoundException('No material found for id '.$itemId);
        }
        $formData = $this->materialTransformer->transform($material);

        $formOptions = ['action' => $this->generateUrl('app_material_editsections', ['roomId' => $roomId, 'itemId' => $itemId])];

        $this->eventDispatcher->dispatch(new CommsyEditEvent($material), CommsyEditEvent::EDIT);

        $form = $this->createForm(MaterialSectionType::class, $formData, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ('save' == $saveType) {
                $formData = $form->getData();

                $material = $this->materialTransformer->applyTransformation($material, $formData);

                $material->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                $material->save();
            } else {
                if ($form->get('cancel')->isClicked()) {
                    return $this->redirectToRoute('app_material_detail',
                        ['roomId' => $roomId, 'itemId' => $itemId]);
                }
            }

            return $this->redirectToRoute('app_material_savesections', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        return $this->render('material/edit_sections.html.twig', ['material' => $material, 'form' => $form, 'sectionList' => $material->getSectionList()->to_array()]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/savesections')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function savesections(
        int $roomId,
        int $itemId
    ): Response {
        $item = $this->itemService->getItem($itemId);
        $material = $this->materialService->getMaterial($itemId);
        $this->eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);

        return $this->render('material/savesections.html.twig', [
            'roomId' => $roomId,
            'item' => $material,
            'sections' => $material->getSectionList()->to_array(),
        ]);
    }

    #[Route(path: '/room/{roomId}/material/{itemId}/{versionId}/createversion/')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function createVersion(
        int $roomId,
        int $itemId,
        int $versionId
    ): RedirectResponse {
        $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

        $material = $this->materialService->getMaterialByVersion($itemId, $versionId);

        $newVersionId = $material->getVersionID() + 1;
        $newMaterial = $material->cloneCopy(true);
        $newMaterial->setVersionID($newVersionId);

        $newMaterial->setModificatorItem($currentUserItem);

        $newMaterial->save();

        return $this->redirectToRoute('app_material_detail', [
            'roomId' => $roomId,
            'itemId' => $itemId,
            'versionId' => $newVersionId,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/material/download')]
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
    #[Route(path: '/room/{roomId}/material/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkRead(
        Request $request,
        MarkReadAction $markReadAction,
        MarkReadMaterial $markReadMaterial,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
        $markReadAction->setMarkReadStrategy($markReadMaterial);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/material/xhr/pin', condition: 'request.isXmlHttpRequest()')]
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
    #[Route(path: '/room/{roomId}/material/xhr/unpin', condition: 'request.isXmlHttpRequest()')]
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
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/material/xhr/mark', condition: 'request.isXmlHttpRequest()')]
    public function xhrMark(
        Request $request,
        MarkAction $action,
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
    #[Route(path: '/room/{roomId}/material/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
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
    #[Route(path: '/room/{roomId}/material/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
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
    #[Route(path: '/room/{roomId}/material/xhr/activate', condition: 'request.isXmlHttpRequest()')]
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
    #[Route(path: '/room/{roomId}/material/xhr/deactivate', condition: 'request.isXmlHttpRequest()')]
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
    #[Route(path: '/room/{roomId}/material/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDelete(
        Request $request,
        DeleteAction $deleteAction,
        DeleteMaterial $deleteMaterial,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $deleteAction->setDeleteStrategy($deleteMaterial);

        return $deleteAction->execute($room, $items);
    }

    /**
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(MaterialFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_material_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    /**
     * @param cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return cs_material_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        // get the material manager service

        if ($selectAll) {
            if ($request->query->has('material_filter')) {
                $currentFilter = $request->query->all('material_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->materialService->setFilterConditions($filterForm);
            } else {
                $this->materialService->hideDeactivatedEntries();
            }

            return $this->materialService->getListMaterials($roomItem->getItemID());
        } else {
            return $this->materialService->getMaterialsById($roomItem->getItemID(), $itemIds);
        }
    }
}
