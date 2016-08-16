<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\MaterialFilterType;
use CommsyBundle\Form\Type\AnnotationType;
use CommsyBundle\Form\Type\MaterialType;
use CommsyBundle\Form\Type\SectionType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class MaterialController extends Controller
{
    /**
     * @Route("/room/{roomId}/material/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $materialFilter = $request->get('materialFilter');
        if (!$materialFilter) {
            $materialFilter = $request->query->get('material_filter');
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');

        if ($materialFilter) {
            // setup filter form
            $defaultFilterValues = array(
                'activated' => true,
            );
            $filterForm = $this->createForm(MaterialFilterType::class, $defaultFilterValues, array(
                'action' => $this->generateUrl('commsy_material_list', array(
                    'roomId' => $roomId,
                )),
                'hasHashtags' => $roomItem->withBuzzwords(),
                'hasCategories' => $roomItem->withTags(),
            ));

            // manually bind values from the request
            $filterForm->submit($materialFilter);
        
            // set filter conditions in material manager
            $materialService->setFilterConditions($filterForm);
        } else {
            $materialService->showNoNotActivatedEntries();
        }

        // get material list from manager service 
        $materials = $materialService->getListMaterials($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $itemIds = array();
            foreach ($materials as $material) {
                $itemIds[] = $material->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
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
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }



        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(MaterialFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_material_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $materialService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $itemsCountArray = $materialService->getCountArray($roomId);




        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(MaterialFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_material_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();


        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $materialService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'material',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showWorkflow' => $roomItem->withWorkflow(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showCategories' => $roomItem->withTags(),
            'material_filter' => $filterForm,
        );
    }

    /**
     * @Route("/room/{roomId}/material/print")
     */
    public function printlistAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(MaterialFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_material_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => $roomItem->withBuzzwords(),
            'hasCategories' => $roomItem->withTags(),
        ));

        // get the material manager service
        $materialService = $this->get('commsy_legacy.material_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $materialService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $materials = $materialService->getListMaterials($roomId);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $itemIds = array();
            foreach ($materials as $material) {
                $itemIds[] = $material->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get material list from manager service 
        $itemsCountArray = $materialService->getCountArray($roomId);

        $html = $this->renderView('CommsyBundle:Material:listPrint.html.twig', [
            'roomId' => $roomId,
            'module' => 'material',
            'materials' => $materials,
            'itemsCountArray' => $itemsCountArray,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
            
        ]);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $this->get('knp_snappy.pdf')->setOption('footer-line',true);
        $this->get('knp_snappy.pdf')->setOption('footer-spacing', 1);
        $this->get('knp_snappy.pdf')->setOption('footer-center',"[page] / [toPage]");
        $this->get('knp_snappy.pdf')->setOption('header-line', true);
        $this->get('knp_snappy.pdf')->setOption('header-spacing', 1 );
        $this->get('knp_snappy.pdf')->setOption('header-right', date("d.m.y"));
        $this->get('knp_snappy.pdf')->setOption('header-left', $roomItem->getTitle());
        $this->get('knp_snappy.pdf')->setOption('header-center', "Commsy");
        $this->get('knp_snappy.pdf')->setOption('images',true);

        // set cookie for authentication - needed to request images
        $this->get('knp_snappy.pdf')->setOption('cookie', [
            'SID' => $legacyEnvironment->getSessionID(),
        ]);

        //return new Response($html);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="print.pdf"',
            ]
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/{versionId}", requirements={
     *     "itemId": "\d+",
     *     "versionId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, $versionId = null, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        if (!$versionId) {
            $material = $materialService->getMaterial($itemId);
        } else {
            $material = $materialService->getMaterialByVersion($itemId, $versionId);
        }

        $infoArray = $this->getDetailInfo($roomId, $itemId, $versionId);

        $wordpressExporter = $this->get('commsy.export.wordpress');
        $canExportToWordpress = false;
        // TODO: check if no version is specified
        // !isset($_GET['version_id'])
        if ($wordpressExporter->isEnabled()) {
            if ($wordpressExporter->isExportAllowed($material)) {
                if ($this->isGranted('ITEM_EDIT', $material->getItemID())) {
                    $canExportToWordpress = true;
                }
            }
        }

        $wikiExporter = $this->get('commsy.export.wiki');
        $canExportToWiki = false;
        // TODO: check if no version is specified
        // !isset($_GET['version_id'])
        if ($wikiExporter->isEnabled()) {
            if ($wikiExporter->isExportAllowed($material)) {
                if ($this->isGranted('ITEM_EDIT', $material->getItemID())) {
                    $canExportToWiki = true;
                }
            }
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);
        
        return array(
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
            'workflowGroupArray'=> $infoArray['workflowGroupArray'],
            'workflowUserArray'=> $infoArray['workflowUserArray'],
            'workflowText' => $infoArray['workflowText'],
            'workflowValidityDate' => $infoArray['workflowValidityDate'],
            'workflowResubmissionDate' => $infoArray['workflowResubmissionDate'],
            'workflowUnread' => $infoArray['workflowUnread'],
            'workflowRead' => $infoArray['workflowRead'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
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
            ]
       );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/workflow", condition="request.isXmlHttpRequest()")
     **/
    public function workflowAction($roomId, $itemId, Request $request)
    {
        if ($request->request->has('read')) {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

            $itemManager = $legacyEnvironment->getItemManager();
            $currentContextItem = $legacyEnvironment->getCurrentContextItem();
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();

            $read = $request->request->get('read');

            if ($currentContextItem->withWorkflow()) {
                if ($read) {
                    $itemManager->markItemAsWorkflowRead($itemId, $currentUserItem->getItemID());
                } else {
                    $itemManager->markItemAsWorkflowNotRead($itemId, $currentUserItem->getItemID());
                }
            } else {
                throw new \Exception('workflow is not enabled');
            }
        }

        $response = new JsonResponse();

        return $response;
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/rating/{vote}")
     * @Template()
     **/
    public function ratingAction($roomId, $itemId, $vote, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $material = $materialService->getMaterial($itemId);
        
        $assessmentService = $this->get('commsy_legacy.assessment_service');
        if ($vote != 'remove') {
            $assessmentService->rateItem($material, $vote);
        } else {
            $assessmentService->removeRating($material);
        }
        
        $assessmentService = $this->get('commsy_legacy.assessment_service');
        $ratingDetail = $assessmentService->getRatingDetail($material);
        $ratingAverageDetail = $assessmentService->getAverageRatingDetail($material);
        $ratingOwnDetail = $assessmentService->getOwnRatingDetail($material);
        
        return array(
            'roomId' => $roomId,
            'material' => $material,
            'ratingArray' =>  array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

    private function getDetailInfo ($roomId, $itemId, $versionId = null) {
        $infoArray = array();
        
        $materialService = $this->get('commsy_legacy.material_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');
        
        if (!$versionId) {
            $material = $materialService->getMaterial($itemId);
        } else {
            $material = $materialService->getMaterialByVersion($itemId, $versionId);
        }
        
        if($material == null) {
            $section = $materialService->getSection($itemId);
            $material = $materialService->getMaterial($section->getLinkedItemID());
        }
        
        $sectionList = $material->getSectionList()->to_array();
        
        $itemArray = array($material);
        $itemArray = array_merge($itemArray, $sectionList);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
 
        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($material->getContextId());        
        $numTotalMember = $roomItem->getAllUsers();

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
		   $id_array[] = $current_user->getItemID();
		   $current_user = $user_list->getNext();
		}
		$readerManager->getLatestReaderByUserIDArray($id_array,$material->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($material->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $material->getModificationDate() ) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
		    $current_user = $user_list->getNext();
		}
        $read_percentage = round(($read_count/$all_user_count) * 100);
        $read_since_modification_percentage = round(($read_since_modification_count/$all_user_count) * 100);
        $readerService = $this->get('commsy_legacy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $materials = $materialService->getListMaterials($roomId);
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
                $lastItemId = $materials[sizeof($materials)-1]->getItemId();
            }
        }

        // workflow
        $workflowGroupArray = array();
        $workflowUserArray = array();
        $workflowRead = false;
        $workflowUnread = false;

        if ($current_context->withWorkflowReader()) {
            $itemManager = $legacyEnvironment->getItemManager();
            $users_read_array = $itemManager->getUsersMarkedAsWorkflowReadForItem($material->getItemID());
            $persons_array = array();
            foreach($users_read_array as $user_read){
                $persons_array[] = $userManager->getItem($user_read['user_id']);
            }

            if($current_context->getWorkflowReaderGroup() == '1'){
                $group_manager = $legacyEnvironment->getGroupManager();
                $group_manager->setContextLimit($legacyEnvironment->getCurrentContextID());
                $group_manager->setTypeLimit('group');
                $group_manager->select();
                $group_list = $group_manager->get();
                $group_item = $group_list->getFirst();
                while($group_item){
                    $link_user_list = $group_item->getLinkItemList('user');
                    $user_count_complete = $link_user_list->getCount();
                    $user_count = 0;
                    foreach($persons_array as $person) {
                        if (!empty($persons_array[0])) {
                            $temp_link_list = $person->getLinkItemList('group');
                            $temp_link_item = $temp_link_list->getFirst();

                            while ($temp_link_item) {
                                $temp_group_item = $temp_link_item->getLinkedItem($person);
                                if($group_item->getItemID() == $temp_group_item->getItemID()) {
                                    $user_count++;
                                }
                                $temp_link_item = $temp_link_list->getNext();
                            }
                        }
                    }
                    $tmpArray = array();
                    $tmpArray['iid'] = $group_item->getItemID();
                    $tmpArray['title']=  $group_item->getTitle();
                    $tmpArray['userCount']=  $user_count;
                    $tmpArray['userCountComplete']=  $user_count_complete;
                    $workflowGroupArray[] = $tmpArray;
                    $group_item = $group_list->getNext();
                }
            }

            if ($current_context->getWorkflowReaderPerson() == '1'){
                foreach ($persons_array as $person) {
                    if (!empty($persons_array[0])){
                        $tmpArray = array();
                        $tmpArray['iid'] = $person->getItemID();
                        $tmpArray['name']=  $person->getFullname();
                        $workflowUserArray[] = $tmpArray;
                    }
                }
            }

            $currentContextItem = $legacyEnvironment->getCurrentContextItem();
            $currentUserItem = $legacyEnvironment->getCurrentUserItem();
            
            if ($currentContextItem->withWorkflow()) {
                if (!$currentUserItem->isRoot()) {
                    if (!$currentUserItem->isGuest() && $material->isReadByUser($currentUserItem)) {
                        $workflowUnread = true;
                    } else  {
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
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $ratingDetail = $assessmentService->getRatingDetail($material);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($material);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($material);
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $reader_manager = $legacyEnvironment->getReaderManager();
        $noticed_manager = $legacyEnvironment->getNoticedManager();

        $item = $material;
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        // mark annotations as read
        $annotationList = $material->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);
 
        $readsectionList = $material->getSectionList();

        $section = $readsectionList->getFirst();
        while($section) {
            $reader = $reader_manager->getLatestReader($section->getItemID());
            if(empty($reader) || $reader['read_date'] < $section->getModificationDate()) {
                $reader_manager->markRead($section->getItemID(), 0);
            }

            $noticed = $noticed_manager->getLatestNoticed($section->getItemID());
            if(empty($noticed) || $noticed['read_date'] < $section->getModificationDate()) {
                $noticed_manager->markNoticed($section->getItemID(), 0);
            }

            $section = $readsectionList->getNext();
        }

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $materialCategories = $material->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $materialCategories);
        }

        $versions = array();
        $versionList = $materialService->getVersionList($material->getItemId())->to_array();
        
        if (sizeof($versionList > 1)) {
            $minTimestamp = time();
            $maxTimestamp = -1;
            $foundCurrent = false;
            $first = true;
            foreach ($versionList as $versionItem) {
                $tempParsedDate = date_parse($versionItem->getModificationDate());
                $tempDateTime = new \DateTime();
                $tempDateTime->setDate($tempParsedDate['year'], $tempParsedDate['month'], $tempParsedDate['day']);
                $tempDateTime->setTime($tempParsedDate['hour'], $tempParsedDate['minute'], $tempParsedDate['second']);
                $tempTimeStamp = $tempDateTime->getTimeStamp();
                $current = false;
                if ($versionId) {
                    if ($versionId == $versionItem->getVersionId()) {
                        $current = true;
                    }
                } else {
                    if ($first) {
                        $current = true;
                        $first = false;
                    }
                }
                $versions[$tempTimeStamp] = array('item' => $versionItem, 'date' => date('d.m.Y H:s', $tempTimeStamp), 'current' => $current);
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
            $toFollow = sizeof($versions)-1;
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
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['ratingArray'] = $current_context->isAssessmentActive() ? [
            'ratingDetail' => $ratingDetail,
            'ratingAverageDetail' => $ratingAverageDetail,
            'ratingOwnDetail' => $ratingOwnDetail,
        ] : [];
        $infoArray['roomCategories'] = $categories;
        $infoArray['versions'] = $versions;
        
        return $infoArray;
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

    /**
     * @Route("/room/{roomId}/material/{itemId}/saveworkflow")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveWorkflowAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $itemService = $this->get('commsy_legacy.item_service');
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
     */
    public function newAction($roomId, Request $request)
    {

    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $materialItem = NULL;
        $isMaterial = false;
        $isDraft = false;
        $isSaved = false;
        
        if ($item->getItemType() == 'material') {
            $isMaterial = true;
            if ($item->isDraft()) {
                $isDraft = true;
            }

            // get material from MaterialService
            $materialItem = $materialService->getMaterial($itemId);
            $materialItem->setDraftStatus($item->isDraft());
            if (!$materialItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }
            $formData = $transformer->transform($materialItem);
            $form = $this->createForm(MaterialType::class, $formData, array(
                'action' => $this->generateUrl('commsy_material_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                ))
            ));
        } else if ($item->getItemType() == 'section') {
            // get section from MaterialService
            $materialItem = $materialService->getSection($itemId);
            if (!$materialItem) {
                throw $this->createNotFoundException('No section found for id ' . $roomId);
            }
            $formData = $transformer->transform($materialItem);
            $form = $this->createForm(SectionType::class, $formData, array());
        }
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $materialItem = $transformer->applyTransformation($materialItem, $form->getData());
                $isSaved = true;

                // update modifier
                $materialItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $materialItem->save();
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('commsy_material_save', array('roomId' => $roomId, 'itemId' => $itemId));
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'isSaved' => $isSaved,
            'isDraft' => $isDraft,
            'isMaterial' => $isMaterial,
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');
        
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
     */
    public function printAction($roomId, $itemId)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $material = $materialService->getMaterial($itemId);

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $html = $this->renderView('CommsyBundle:Material:detailPrint.html.twig', [
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
            'workflowGroupArray'=> $infoArray['workflowGroupArray'],
            'workflowUserArray'=> $infoArray['workflowUserArray'],
            'workflowText' => $infoArray['workflowText'],
            'workflowValidityDate' => $infoArray['workflowValidityDate'],
            'workflowResubmissionDate' => $infoArray['workflowResubmissionDate'],
            'workflowUnread' => $infoArray['workflowUnread'],
            'workflowRead' => $infoArray['workflowRead'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'ratingArray' => $infoArray['ratingArray'],
            'roomCategories' => $infoArray['roomCategories'],
        ]);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get room item for information panel
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $this->get('knp_snappy.pdf')->setOption('footer-line',true);
        $this->get('knp_snappy.pdf')->setOption('footer-spacing', 1);
        $this->get('knp_snappy.pdf')->setOption('footer-center',"[page] / [toPage]");
        $this->get('knp_snappy.pdf')->setOption('header-line', true);
        $this->get('knp_snappy.pdf')->setOption('header-spacing', 1 );
        $this->get('knp_snappy.pdf')->setOption('header-right', date("d.m.y"));
        $this->get('knp_snappy.pdf')->setOption('header-left', $roomItem->getTitle());
        $this->get('knp_snappy.pdf')->setOption('header-center', "Commsy");
        $this->get('knp_snappy.pdf')->setOption('images',true);

        // set cookie for authentication - needed to request images
        $this->get('knp_snappy.pdf')->setOption('cookie', [
            'SID' => $legacyEnvironment->getSessionID(),
        ]);

        //return new Response($html);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="print.pdf"',
            ]
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/download")
     */
    public function downloadAction($roomId, $itemId)
    {
        $downloadService = $this->get('commsy_legacy.download_service');
        
        $zipFile = $downloadService->zipFile($roomId, $itemId);

        $response = new BinaryFileResponse($zipFile);
        $response->deleteFileAfterSend(true);

        $filename = 'CommSy_Material.zip';
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }
        
    /**
     * @Route("/room/{roomId}/material/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        
        $translator = $this->get('translator');
        
        $materialData = array();
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');
        
        // create new material item
        $materialItem = $materialService->getNewMaterial();
        $materialItem->setTitle('['.$translator->trans('insert title').']');
        $materialItem->setBibKind('none');
        $materialItem->setDraftStatus(1);
        $materialItem->setPrivateEditing('1');
        if ($roomItem->withWorkflow()) {
            $materialItem->setWorkflowTrafficLight($roomItem->getWorkflowTrafficLightDefault());
        }
        $materialItem->save();

        /* $form = $this->createForm('material', $materialData, array());
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $materialItem = $transformer->applyTransformation($materialItem, $form->getData());
            $materialItem->save();
            return $this->redirectToRoute('commsy_material_detail', array('roomId' => $roomId, 'itemId' => $materialItem->getItemId()));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        } */

        return $this->redirectToRoute('commsy_material_detail', array('roomId' => $roomId, 'itemId' => $materialItem->getItemId()));

        /* return array(
            'material' => $materialItem,
            'form' => $form->createView()
        ); */
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/createsection")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function createSectionAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');

        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');

        $material = $materialService->getMaterial($itemId);

        $sectionList = $material->getSectionList();
        $sections = $sectionList->to_array();
        $countSections = $sectionList->getCount();

        $section = $materialService->getNewSection();
        $section->setTitle('['.$translator->trans('insert title').']');
        $section->setLinkedItemId($itemId);
        $section->setNumber($countSections+1);
        $section->save();

        $formData = $transformer->transform($section);
        $form = $this->createForm(SectionType::class, $formData, array(
            'action' => $this->generateUrl('commsy_material_savesection', array('roomId' => $roomId, 'itemId' => $section->getItemID()))
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
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveSectionAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');

        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get section
        $section = $materialService->getSection($itemId);

        $form = $this->createForm(SectionType::class);

        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // update title
                $section->setTitle($form->getData()['title']);

                // update modifier
                $section->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $section->save();
                
            } else if ($form->get('cancel')->isClicked()) {
                // remove not saved item
                $section->delete();

                $section->save();
            }
            return $this->redirectToRoute('commsy_material_detail', array('roomId' => $roomId, 'itemId' => $section->getLinkedItemID()));
        }
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}/sortsections")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function sortSectionsAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');

        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        // get section
        $material = $materialService->getMaterial($itemId);

        $json = json_decode($request->getContent());

        $i = 1;
        foreach ($json as $key => $value) {
            // set sorting
            $section = $materialService->getSection($value[0]);
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
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editSectionsAction($roomId, $itemId, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');

        $material = $materialService->getMaterial($itemId);

        $sectionList = $material->getSectionList()->to_array();

        return array(
            'sectionList' => $sectionList,
            'material' => $material
        );
    }
    
    /**
     * @Route("/room/{roomId}/material/feedaction")
     */
    public function feedActionAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $action = $request->request->get('act');
        
        $selectedIds = $request->request->get('data');
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
        }
        
        $selectAll = $request->request->get('selectAll');
        $selectAllStart = $request->request->get('selectAllStart');
        
        if ($selectAll == 'true') {
            $entries = $this->feedAction($roomId, $max = 1000, $start = $selectAllStart, $request);
            foreach ($entries['materials'] as $key => $value) {
                $selectedIds[] = $value->getItemId();
            }
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');

        $result = [];
        
        if ($action == 'markread') {
	        $materialService = $this->get('commsy_legacy.material_service');
	        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $noticedManager = $legacyEnvironment->getNoticedManager();
            $readerManager = $legacyEnvironment->getReaderManager();
            foreach ($selectedIds as $id) {
    	        $item = $materialService->getMaterial($id);
    	        $versionId = $item->getVersionID();
    	        $noticedManager->markNoticed($id, $versionId);
    	        $readerManager->markRead($id, $versionId);
    	        
    	        $sectionList =$item->getSectionList();
    	        if ( !empty($sectionList) ){
    	            $sectionItem = $sectionList->getFirst();
    	            while($sectionItem){
    	               $noticedManager->markNoticed($sectionItem->getItemID(),$versionId);
    	               $readerManager->markRead($sectionItem->getItemID(),$versionId);
    	               $sectionItem = $sectionList->getNext();
    	            }
    	        }
    	        
    	        $annotationList =$item->getAnnotationList();
    	        if ( !empty($annotationList) ){
    	            $annotationItem = $annotationList->getFirst();
    	            while($annotationItem){
    	               $noticedManager->markNoticed($annotationItem->getItemID(),$versionId);
    	               $readerManager->markRead($annotationItem->getItemID(),$versionId);
    	               $annotationItem = $annotationList->getNext();
    	            }
    	        }
	        }
	        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('marked %count% entries as read',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'copy') {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $sessionItem = $legacyEnvironment->getSessionItem();

            $currentClipboardIds = array();
            if ($sessionItem->issetValue('clipboard_ids')) {
                $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
            }

            foreach ($selectedIds as $itemId) {
                if (!in_array($itemId, $currentClipboardIds)) {
                    $currentClipboardIds[] = $itemId;
                    $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
                }
            }

            $result = [
                'count' => sizeof($currentClipboardIds)
            ];

            $sessionManager = $legacyEnvironment->getSessionManager();
            $sessionManager->save($sessionItem);

            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'save') {
            /* $zipfile = $this->download($roomId, $selectedIds);
            $content = file_get_contents($zipfile);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => 'application/zip'));
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'zipfile.zip');   
            $response->headers->set('Content-Disposition', $contentDisposition);
            
            return $response; */
            
            $downloadService = $this->get('commsy_legacy.download_service');
        
            $zipFile = $downloadService->zipFile($roomId, $selectedIds);
    
            $response = new BinaryFileResponse($zipFile);
            $response->deleteFileAfterSend(true);
    
            $filename = 'CommSy_Material.zip';
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);   
            $response->headers->set('Content-Disposition', $contentDisposition);
    
            return $response;
        } else if ($action == 'delete') {
            $materialService = $this->get('commsy_legacy.material_service');
  		    foreach ($selectedIds as $id) {
  		        $item = $materialService->getMaterial($id);
  		        $item->delete();
  		    }
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> '.$translator->transChoice('%count% deleted entries',count($selectedIds), array('%count%' => count($selectedIds)));
        }

        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/{versionId}/createversion/")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function createVersionAction($roomId, $itemId, $versionId, Request $request)
    {           
        $materialService = $this->get('commsy_legacy.material_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUserItem = $legacyEnvironment->getCurrentUserItem();

        $annotationService = $this->get('commsy_legacy.annotation_service');
        
        $material = $materialService->getMaterialByVersion($itemId, $versionId);

        $newVersionId = $material->getVersionID()+1;
        $newMaterial = $material->cloneCopy(true);
        $newMaterial->setVersionID($newVersionId);
        
        $newMaterial->setModificatorItem($currentUserItem);
        
        $newMaterial->save();

        return $this->redirectToRoute('commsy_material_detail', array('roomId' => $roomId, 'itemId' => $itemId, 'versionId' => $newVersionId));
    }
}