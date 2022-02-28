<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Delete\DeleteAction;
use App\Action\Delete\DeleteMaterial;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\MarkReadAction;
use App\Action\MarkRead\MarkReadMaterial;
use App\Entity\License;
use App\Event\CommsyEditEvent;
use App\Filter\MaterialFilterType;
use App\Form\DataTransformer\MaterialTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\MaterialSectionType;
use App\Form\Type\MaterialType;
use App\Form\Type\SectionType;
use App\Http\JsonRedirectResponse;
use App\Services\LegacyEnvironment;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AnnotationService;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\AssessmentService;
use App\Utils\RoomService;
use cs_item;
use cs_tag_item;
use cs_buzzword_item;
use cs_buzzword_manager;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use App\Utils\MaterialService;
use App\Utils\TopicService;
use cs_material_item;
use cs_room_item;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class MaterialController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'material')")
 */
class MaterialController extends BaseController
{
    /**
     * @var MaterialService
     */
    private $materialService;

    /**
     * @var AnnotationService
     */
    private $annotationService;

    /**
     * @var CategoryService
     */
    private $categoryService;

    /**
     * @var MaterialTransformer
     */
    private $materialTransformer;

    /**
     * @var AssessmentService
     */
    private $assessmentService;
    private SessionInterface $session;

    /**
     * @required
     * @param CategoryService $categoryService
     */
    public function setCategoryService(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * @required
     * @param mixed $materialService
     */
    public function setMaterialService(MaterialService $materialService): void
    {
        $this->materialService = $materialService;
    }

    /**
     * @required
     * @param mixed $annotationService
     */
    public function setAnnotationService(AnnotationService $annotationService): void
    {
        $this->annotationService = $annotationService;
    }

    /**
     * @required
     * @param MaterialTransformer $materialTransformer
     */
    public function setMaterialTransformer(MaterialTransformer $materialTransformer)
    {
        $this->materialTransformer = $materialTransformer;
    }

    /**
     * @required
     * @param AssessmentService $assessmentService
     */
    public function setAssessmentService(AssessmentService $assessmentService): void
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * @required
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }



    /**
     * @Route("/room/{roomId}/material/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'date'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $materialFilter = $request->get('materialFilter');
        if (!$materialFilter) {
            $materialFilter = $request->query->get('material_filter');
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

        // get material list from manager service 
        $materials = $this->materialService->getListMaterials($roomId, $max, $start, $sort);

        $this->session->set('sortMaterials', $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($materials as $material) {
                $itemIds[] = $material->getItemId();
            }
            $ratingList = $this->assessmentService->getListAverageRatings($itemIds);
        }

        return array(
            'roomId' => $roomId,
            'materials' => $materials,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ]
        );
    }

    /**
     * @Route("/room/{roomId}/material")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        int $roomId
    ) {

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

        // get material list from manager service 
        $itemsCountArray = $this->materialService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('material') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('material');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('material');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'material',
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
            'isArchived' => $roomItem->isArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'isMaterialOpenForGuests' => $roomItem->isMaterialOpenForGuests(),
        );
    }

    /**
     * @Route("/room/{roomId}/material/print/{sort}", defaults={"sort" = "none"})
     * @param Request $request
     * @param PrintService $printService
     * @param int $roomId
     * @param string $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        PrintService $printService,
        int $roomId,
        string $sort
    ) {
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
        if ($sort != "none") {
            $materials = $this->materialService->getListMaterials($roomId, $numAllMaterials, 0, $sort);
        } elseif ($this->session->get('sortMaterials')) {
            $materials = $this->materialService->getListMaterials($roomId, $numAllMaterials, 0,
                $this->session->get('sortMaterials'));
        } else {
            $materials = $this->materialService->getListMaterials($roomId, $numAllMaterials, 0, 'date');
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
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

    /**
     * @Route("/room/{roomId}/material/{itemId}/{versionId}", requirements={
     *     "itemId": "\d+",
     *     "versionId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param Request $request
     * @param TopicService $topicService
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @param int|null $versionId
     * @return array
     */
    public function detailAction(
        Request $request,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId,
        int $versionId = null
    ) {
        $roomItem = $this->getRoom($roomId);
        if ($versionId === null) {
            $material = $this->materialService->getMaterial($itemId);
        } else {
            $material = $this->materialService->getMaterialByVersion($itemId, $versionId);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId, $versionId);

        $canExportToWordpress = false;
        // TODO: check if no version is specified
        // !isset($_GET['version_id'])


        $canExportToWiki = false;

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($material->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        $legacyMarkup->addFiles($this->itemService->getItemFileList($itemId));

        $amountAnnotations = $this->annotationService->getListAnnotations($roomId, $infoArray['material']->getItemId(),
            null, null);

        return array(
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
            'annotationForm' => $form->createView(),
            'ratingArray' => $infoArray['ratingArray'],
            'canExportToWordpress' => $canExportToWordpress,
            'canExportToWiki' => $canExportToWiki,
            'roomCategories' => $infoArray['roomCategories'],
            'versions' => $infoArray['versions'],
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/workflow", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @return JsonRedirectResponse
     * @throws Exception
     */
    public function workflowAction(
        Request $request,
        int $roomId,
        int $itemId
    ) {
        if ($request->request->has('payload')) {
            $payload = $request->request->get('payload');

            if (isset($payload['read']) && $payload['read']) {
                $read = $payload['read'];

                $itemManager = $this->legacyEnvironment->getItemManager();
                $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
                $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();

                if ($currentContextItem->withWorkflow()) {
                    if ($read == 'true') {
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
            'itemId' => $itemId
        ]));
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/rating/{vote}")
     * @Template()
     * @param int $roomId
     * @param int $itemId
     * @param string $vote
     * @return array
     */
    public function ratingAction(
        int $roomId,
        int $itemId,
        string $vote
    ) {
        $material = $this->materialService->getMaterial($itemId);
        if ($vote != 'remove') {
            $this->assessmentService->rateItem($material, $vote);
        } else {
            $this->assessmentService->removeRating($material);
        }
        $ratingDetail = $this->assessmentService->getRatingDetail($material);
        $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($material);
        $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($material);

        return array(
            'roomId' => $roomId,
            'material' => $material,
            'ratingArray' => array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

    private function getDetailInfo(
        int $roomId,
        int $itemId,
        int $versionId = null
    ) {
        $infoArray = array();

        /** @var cs_material_item $material */
        $material = null;
        if ($versionId === null) {
            $material = $this->materialService->getMaterial($itemId);
        } else {
            $material = $this->materialService->getMaterialByVersion($itemId, $versionId);
        }

        if ($material == null) {
            $section = $this->materialService->getSection($itemId);
            $material = $this->materialService->getMaterial($section->getLinkedItemID());
        }

        $sectionList = $material->getSectionList()->to_array();

        $itemArray = array($material);
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

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $material->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($material->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $material->getModificationDate()) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }

        $readerList = array();
        $modifierList = array();
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
        $materialList = array();
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
                    $counterBefore++;
                }
                $materialList[] = $tempMaterial;
                if ($tempMaterial->getItemID() == $material->getItemID()) {
                    $foundMaterial = true;
                }
                if (!$foundMaterial) {
                    $prevItemId = $tempMaterial->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $materialList[] = $tempMaterial;
                    $counterAfter++;
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
            $persons_array = array();
            foreach ($users_read_array as $user_read) {
                $persons_array[] = $userManager->getItem($user_read['user_id']);
            }

            if ($current_context->getWorkflowReaderGroup() == '1') {
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
                                    $user_count++;
                                }
                                $temp_link_item = $temp_link_list->getNext();
                            }
                        }
                    }
                    $tmpArray = array();
                    $tmpArray['iid'] = $group_item->getItemID();
                    $tmpArray['title'] = $group_item->getTitle();
                    $tmpArray['userCount'] = $user_count;
                    $tmpArray['userCountComplete'] = $user_count_complete;
                    $workflowGroupArray[] = $tmpArray;
                    $group_item = $group_list->getNext();
                }
            }

            if ($current_context->getWorkflowReaderPerson() == '1') {
                foreach ($persons_array as $person) {
                    if (!empty($persons_array[0])) {
                        $tmpArray = array();
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
            switch ($material->getWorkflowTrafficLight()) {
                case '0_green':
                    $workflowText = $current_context->getWorkflowTrafficLightTextGreen();
                    break;
                case '1_yellow':
                    $workflowText = $current_context->getWorkflowTrafficLightTextYellow();
                    break;
                case '2_red':
                    $workflowText = $current_context->getWorkflowTrafficLightTextRed();
                    break;
                default:
                    $workflowText = '';
                    break;
            }
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $this->assessmentService->getRatingDetail($material);
            $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($material);
            $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($material);
        }

        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $noticed_manager = $this->legacyEnvironment->getNoticedManager();

        $item = $material;
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        // mark annotations as read
        $annotationList = $material->getAnnotationList();
        $this->annotationService->markAnnotationsReadedAndNoticed($annotationList);

        $readsectionList = $material->getSectionList();

        $section = $readsectionList->getFirst();
        while ($section) {
            $reader = $reader_manager->getLatestReader($section->getItemID());
            if (empty($reader) || $reader['read_date'] < $section->getModificationDate()) {
                $reader_manager->markRead($section->getItemID(), 0);
            }

            $noticed = $noticed_manager->getLatestNoticed($section->getItemID());
            if (empty($noticed) || $noticed['read_date'] < $section->getModificationDate()) {
                $noticed_manager->markNoticed($section->getItemID(), 0);
            }

            $section = $readsectionList->getNext();
        }

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->categoryService->getTags($roomId);
            $materialCategories = $material->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $materialCategories);
        }

        $versions = array();
        $versionList = $this->materialService->getVersionList($material->getItemId())->to_array();

        if (count($versionList) > 1) {
            $minTimestamp = time();
            $maxTimestamp = -1;
            $first = true;
            foreach ($versionList as $versionItem) {
                $tempParsedDate = date_parse($versionItem->getModificationDate());
                $tempDateTime = new \DateTime();
                $tempDateTime->setDate($tempParsedDate['year'], $tempParsedDate['month'], $tempParsedDate['day']);
                $tempDateTime->setTime($tempParsedDate['hour'], $tempParsedDate['minute'], $tempParsedDate['second']);
                $tempTimeStamp = $tempDateTime->getTimeStamp();
                $current = false;
                if ($versionId !== null) {
                    if ($versionId == $versionItem->getVersionId()) {
                        $current = true;
                    }
                } else {
                    if ($first) {
                        $current = true;
                        $first = false;
                    }
                }
                $versions[$tempTimeStamp] = array(
                    'item' => $versionItem,
                    'date' => date('d.m.Y H:i', $tempTimeStamp),
                    'current' => $current
                );
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
                            $tempPercent += 1;
                        }
                    }
                } else {
                    $first = false;
                }

                if ($tempPercent >= 95) {
                    if ($toFollow != 0) {
                        $tempPercent = $tempPercent - ($toFollow * 2);
                    }
                }

                $versions[$timestamp]['percent'] = $tempPercent;
                $lastPercent = $tempPercent;
                $toFollow--;
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
                        $result[] = array(
                            'title' => $baseCategory['title'],
                            'item_id' => $baseCategory['item_id'],
                            'children' => $tempResult
                        );
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array(
                        'title' => $baseCategory['title'],
                        'item_id' => $baseCategory['item_id'],
                        'children' => $tempResult
                    );
                }
            }
            $tempResult = array();
            $addCategory = false;
        }
        return $result;
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/saveworkflow")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveWorkflowAction(
        int $roomId,
        int $itemId
    ) {
        $roomItem = $this->getRoom($roomId);
        $item = $this->itemService->getItem($itemId);
        $tempItem = null;

        if ($item->getItemType() == 'material') {
            $tempItem = $this->materialService->getMaterial($itemId);
        }

        $itemArray = array($tempItem);

        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'modifierList' => $modifierList,
            'workflowGroupArray' => $infoArray['workflowGroupArray'],
            'workflowUserArray' => $infoArray['workflowUserArray'],
            'workflowText' => $infoArray['workflowText'],
            'workflowValidityDate' => $infoArray['workflowValidityDate'],
            'workflowResubmissionDate' => $infoArray['workflowResubmissionDate'],
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ]
        );
    }

    /**
     * @Route("/room/{roomId}/material/new")
     * @Template()
     * @param Request $request
     * @param int $roomId
     */
    public function newAction(
        Request $request,
        int $roomId
    ) {

    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param Request $request
     * @param ItemController $itemController
     * @param CategoryService $categoryService
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        ItemController $itemController,
        CategoryService $categoryService,
        ItemService $itemService,
        RoomService $roomService,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        LabelService $labelService,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        // NOTE: this method currently gets used for both, material & section items
        // TODO: move handling of sections into a dedicated `editSectionAction()`
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $typedItem = null;
        $isMaterial = false;
        $isDraft = false;
        $isSaved = false;

        $formData = array();
        $optionsData = array();
        $items = array();

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

        $itemManager->setIntervalLimit(40 + $countLinked);
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
        $optionsData['categories'] = $this->getCategories($roomId, $categoryService);
        $formData['categories'] = $this->getLinkedCategories($item);
        $categoryConstraints = ($current_context->withTags() && $current_context->isTagMandatory()) ? [new Count(array('min' => 1))] : array();

        // get all hashtags -> list
        $optionsData['hashtags'] = $this->getHashtags($roomId, $legacyEnvironment);
        $formData['hashtags'] = $this->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $hashtagConstraints = ($current_context->withBuzzwords() && $current_context->isBuzzwordMandatory()) ? [new Count(array('min' => 1))] : [];

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        if ($item->isDraft()) {
            // if a material is a draft, allow for categegories and
            // hashtags for being edited, if they are active
            $categoriesMandatory = $current_context->withTags();
            $hashtagsMandatory = $current_context->withBuzzwords();
        } else {
            $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
            $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();
        }


        $licenses = [];
        $licensesContent = [];

        if ($item->getItemType() == 'material') {
            $isMaterial = true;
            if ($item->isDraft()) {
                $isDraft = true;
            }

            // get material from MaterialService
            $materialItem = $this->materialService->getMaterial($itemId);
            $typedItem = $materialItem;
            $materialItem->setDraftStatus($item->isDraft());
            if (!$materialItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }

            $formData = $this->materialTransformer->transform($materialItem);
            $formData['categoriesMandatory'] = $categoriesMandatory;
            $formData['hashtagsMandatory'] = $hashtagsMandatory;
            $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
            $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId,
                $this->legacyEnvironment);

            $licensesRepository = $this->getDoctrine()->getRepository(License::class);
            $availableLicenses = $licensesRepository->findByContextOrderByPosition($this->legacyEnvironment->getCurrentPortalId());
            foreach ($availableLicenses as $availableLicense) {
                $licenses[$availableLicense->getTitle()] = $availableLicense->getId();
                $licensesContent[$availableLicense->getId()] = $availableLicense->getContent();
            }

            $form = $this->createForm(MaterialType::class, $formData, array(
                'action' => $this->generateUrl('app_material_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                )),
                'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
                'categoryMappingOptions' => [
                    'categories' => $itemController->getCategories($roomId, $categoryService),
                    'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                    'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId])
                ],
                'hashtagMappingOptions' => [
                    'hashtags' => $itemController->getHashtags($roomId, $this->legacyEnvironment),
                    'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                    'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
                ],
                'licenses' => $licenses,
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
            ));

            $this->eventDispatcher->dispatch(new CommsyEditEvent($materialItem), CommsyEditEvent::EDIT);

        } else {
            if ($item->getItemType() == 'section') {
                // get section from MaterialService
                $section = $this->materialService->getSection($itemId);
                $typedItem = $section;
                if (!$section) {
                    throw $this->createNotFoundException('No section found for id ' . $roomId);
                }
                $formData = $this->materialTransformer->transform($section);
                $form = $this->createForm(SectionType::class, $formData, array(
                    'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
                ));

                $this->eventDispatcher->dispatch(new CommsyEditEvent($this->materialService->getMaterial($section->getlinkedItemID())),
                    CommsyEditEvent::EDIT);
            }
        }

        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked()) {
                $typedItem = $this->materialTransformer->applyTransformation($typedItem, $form->getData());

                // update modifier
                $typedItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                // set linked hashtags and categories
                $formData = $form->getData();
                if ($categoriesMandatory) {
                    $categoryIds = $formData['category_mapping']['categories'] ?? [];

                    if (isset($formData['category_mapping']['newCategory'])) {
                        $newCategoryTitle = $formData['category_mapping']['newCategory'];
                        $newCategory = $categoryService->addTag($newCategoryTitle, $roomId);
                        $categoryIds[] = $newCategory->getItemID();
                    }

                    if (!empty($categoryIds)) {
                        $typedItem->setTagListByID($categoryIds);
                    }
                }
                if ($hashtagsMandatory) {
                    $hashtagIds = $formData['hashtag_mapping']['hashtags'] ?? [];

                    if (isset($formData['hashtag_mapping']['newHashtag'])) {
                        $newHashtagTitle = $formData['hashtag_mapping']['newHashtag'];

                        $newHashtag = $labelService->getNewHashtag($newHashtagTitle, $roomId);
                        $hashtagIds[] = $newHashtag->getItemID();

                        $hashtagaIds[] = $newHashtag->getItemID();

                    }

                    if (!empty($hashtagIds)) {
                        $typedItem->setBuzzwordListByID($hashtagIds);
                    }
                }

                $typedItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }

                if ($typedItem->getItemType() == CS_SECTION_TYPE) {
                    $linkedMaterialItem = $this->materialService->getMaterial($typedItem->getlinkedItemID());
                    $linkedMaterialItem->save();
                }

                return $this->redirectToRoute('app_material_save', array('roomId' => $roomId, 'itemId' => $itemId));
            }
        }

        return array(
            'isSaved' => $isSaved,
            'isDraft' => $isDraft,
            'isMaterial' => $isMaterial,
            'form' => $form->createView(),
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
            'material' => $typedItem,
            'licenses' => $licenses,
            'licensesContent' => $licensesContent,
            'roomId' => $roomId,
            'itemId' => $itemId,
            'items' => $items,
        );
    }

    public function getCategories($roomId, $categoryService) {
        $categories = $categoryService->getTags($roomId);
        return $this->transformTagArray($categories);
    }

    private function transformTagArray($tagArray)
    {
        $array = [];

        foreach ($tagArray as $tag) {
            // NOTE: in order to form unique array keys, we append the category (aka tag) ID to the category title;
            // note that, in any form that makes use of this tag array, the category ID must be stripped again
            // from the title (e.g. via a `choice_label` field option)
            $array[$tag['title'] . '_' . $tag['item_id']] = $tag['item_id'];

            if (!empty($tag['children'])) {
                $array[$tag['title'] . '_sub' . '_' . $tag['item_id']] = $this->transformTagArray($tag['children']);
            }
        }

        return $array;
    }

    /**
     * @param cs_item $item
     * @return cs_tag_item[]
     */
    public function getLinkedCategories($item) {
        /** @var cs_item $item */

        $linkedCategories = [];
        $categoriesList = $item->getTagList();

        /** @var cs_tag_item $categoryItem */
        $categoryItem = $categoriesList->getFirst();
        while ($categoryItem) {
            $linkedCategories[] = $categoryItem->getItemId();
            $categoryItem = $categoriesList->getNext();
        }
        return $linkedCategories;
    }

    public function getHashtags($roomId, $legacyEnvironment) {
        $hashtags = [];

        /** @var cs_buzzword_manager $buzzwordManager */
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

        /** @var cs_buzzword_manager $buzzwordManager */
        $buzzwordManager = $legacyEnvironment->getBuzzwordManager();
        $buzzwordManager->setContextLimit($roomId);
        $buzzwordManager->setTypeLimit('buzzword');
        $buzzwordManager->select();
        $buzzwordList = $buzzwordManager->get();

        /** @var cs_buzzword_item $buzzwordItem */
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
     * @Route("/room/{roomId}/material/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        int $roomId,
        int $itemId
    ) {
        $roomItem = $this->getRoom($roomId);
        $item = $this->itemService->getItem($itemId);
        $tempItem = null;

        if ($item->getItemType() == 'material') {
            $tempItem = $this->materialService->getMaterial($itemId);

            $this->eventDispatcher->dispatch(new CommsyEditEvent($tempItem), CommsyEditEvent::SAVE);
        } else {
            if ($item->getItemType() == 'section') {
                $tempItem = $this->materialService->getSection($itemId);

                $this->eventDispatcher->dispatch(new CommsyEditEvent($this->materialService->getMaterial($tempItem->getLinkedItemID())),
                    CommsyEditEvent::SAVE);
            }
        }

        $itemArray = array($tempItem);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        return array(
            'roomId' => $roomId,
            'item' => $tempItem,
            'modifierList' => $modifierList,
            'userCount' => $infoArray['userCount'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'workflowTitles' => [
                '0_green' => $roomItem->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $roomItem->getWorkflowTrafficLightTextYellow(),
                '2_red' => $roomItem->getWorkflowTrafficLightTextRed(),
                '3_none' => '',
            ]
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/print")
     * @param PrintService $printService
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        PrintService $printService,
        int $roomId,
        int $itemId
    ) {
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

    /**
     * @Route("/room/{roomId}/material/create")
     * @Template()
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'material')")
     */
    public function createAction(
        int $roomId
    ) {
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

        return $this->redirectToRoute('app_material_detail',
            array('roomId' => $roomId, 'itemId' => $materialItem->getItemId()));
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/createsection")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function createSectionAction(
        int $roomId,
        int $itemId
    ) {
        $material = $this->materialService->getMaterial($itemId);
        $sectionList = $material->getSectionList();
        $countSections = $sectionList->getCount();

        $section = $this->materialService->getNewSection();
        $section->setDraftStatus(1);
        $section->setLinkedItemId($itemId);
        $section->setVersionId($material->getVersionId());
        $section->setNumber($countSections + 1);
        $section->save();

        $formData = $this->materialTransformer->transform($section);
        $form = $this->createForm(SectionType::class, $formData, array(
            'action' => $this->generateUrl('app_material_savesection',
                array('roomId' => $roomId, 'itemId' => $section->getItemID())),
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
        ));

        return array(
            'form' => $form->createView(),
            'sectionList' => $sectionList,
            'material' => $material,
            'section' => $section,
            'modifierList' => array(),
            'userCount' => 0,
            'readCount' => 0,
            'readSinceModificationCount' => 0
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/savesection")
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @return RedirectResponse
     */
    public function saveSectionAction(
        Request $request,
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);

        // get section
        $section = $this->materialService->getSection($itemId);

        $formData = $this->materialTransformer->transform($section);

        $form = $this->createForm(SectionType::class, $formData, array(
            'action' => $this->generateUrl('app_material_savesection',
                array('roomId' => $roomId, 'itemId' => $section->getItemID())),
            'placeholderText' => '[' . $this->translator->trans('insert title') . ']',
        ));

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
            array('roomId' => $roomId, 'itemId' => $section->getLinkedItemID()));
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/sortsections")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param Request $request
     * @param int $itemId
     * @return array
     */
    public function sortSectionsAction(
        Request $request,
        int $itemId
    ) {
        // get section
        $material = $this->materialService->getMaterial($itemId);

        $json = json_decode($request->getContent());

        $i = 1;
        foreach ($json as $key => $value) {
            // set sorting
            $section = $this->materialService->getSection($value[0]);
            $section->setNumber($i);
            $section->save();
            $i++;
        }


        $sectionList = $material->getSectionList()->to_array();

        return array(
            'sectionList' => $sectionList,
            'material' => $material
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/editsections")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editSectionsAction(
        Request $request,
        int $roomId,
        int $itemId
    ) {
        $material = $this->materialService->getMaterial($itemId);
        $item = $this->itemService->getItem($itemId);

        if (!$material) {
            throw $this->createNotFoundException('No material found for id ' . $itemId);
        }
        $formData = $this->materialTransformer->transform($material);

        $formOptions = array(
            'action' => $this->generateUrl('app_material_editsections', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
        );

        $this->eventDispatcher->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($material));

        $form = $this->createForm(MaterialSectionType::class, $formData, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saveType = $form->getClickedButton()->getName();
            if ($saveType == 'save') {
                $formData = $form->getData();

                $material = $this->materialTransformer->applyTransformation($material, $formData);

                $material->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                $material->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else {
                if ($form->get('cancel')->isClicked()) {
                    return $this->redirectToRoute('app_material_detail',
                        array('roomId' => $roomId, 'itemId' => $itemId));
                }
            }
            return $this->redirectToRoute('app_material_savesections', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'material' => $material,
            'form' => $form->createView(),
            'sectionList' => $material->getSectionList()->to_array(),
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/savesections")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function savesectionsAction(
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);
        $material = $this->materialService->getMaterial($itemId);
        $this->eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);
        return [
            'roomId' => $roomId,
            'item' => $material,
            'sections' => $material->getSectionList()->to_array(),
        ];
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/{versionId}/createversion/")
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'material')")
     * @param int $roomId
     * @param int $itemId
     * @param int $versionId
     * @return RedirectResponse
     */
    public function createVersionAction(
        int $roomId,
        int $itemId,
        int $versionId
    ) {
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
            'versionId' => $newVersionId
        ]);
    }

    /**
     * @Route("/room/{roomId}/material/download")
     * @param Request $request
     * @param DownloadAction $action
     * @param int $roomId
     * @return Response
     * @throws Exception
     */
    public function downloadAction(
        Request $request,
        DownloadAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/material/xhr/markread", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        MarkReadAction $markReadAction,
        MarkReadMaterial $markReadMaterial,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);
        $markReadAction->setMarkReadStrategy($markReadMaterial);
        return $markReadAction->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/material/xhr/copy", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrCopyAction(
        Request $request,
        CopyAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/material/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $deleteAction,
        DeleteMaterial $deleteMaterial,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $deleteAction->setDeleteStrategy($deleteMaterial);
        return $deleteAction->execute($room, $items);
    }

    /**
     * @param cs_room_item $room
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
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
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
                $currentFilter = $request->query->get('material_filter');
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