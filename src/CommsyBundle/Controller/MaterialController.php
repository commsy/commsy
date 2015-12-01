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

use \ZipArchive;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MaterialController extends Controller
{
    /**
     * @Route("/room/{roomId}/material/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
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
        $filterForm = $this->createForm(new MaterialFilterType(), $defaultFilterValues, array(
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
        $materials = $materialService->getListMaterials($roomId, $max, $start);

        $readerService = $this->get('commsy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($materials as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        return array(
            'roomId' => $roomId,
            'materials' => $materials,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow()
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
        $filterForm = $this->createForm(new MaterialFilterType(), $defaultFilterValues, array(
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
        $filterForm = $this->createForm(new MaterialFilterType(), $defaultFilterValues, array(
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
            'showCategories' => $roomItem->withTags(),
        );
    }

    /**
     * @Route("/room/{roomId}/material/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm('annotation');
        
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
            'workflowText'=>$infoArray['workflowText'],
            'workflowValidityDate'=>$infoArray['workflowValidityDate'],
            'workflowResubmissionDate'=>$infoArray['workflowResubmissionDate'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
       );
    }

    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $materialService = $this->get('commsy_legacy.material_service');
        $itemService = $this->get('commsy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');
        
        $material = $materialService->getMaterial($itemId);
        if($material == null) {
            $section = $materialService->getSection($itemId);
            $material = $materialService->getMaterial($section->getLinkedItemID());

        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $material;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
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
        $readerService = $this->get('commsy.reader_service');
        
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
        $workflowGroupArray = array();
        $workflowUserArray = array();
       if($current_context->withWorkflowReader()){
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
                    foreach($persons_array as $person){
                    if (!empty($persons_array[0])){
                        $temp_link_list = $person->getLinkItemList('group');
                        $temp_link_item = $temp_link_list->getFirst();
                        while($temp_link_item){
                            $temp_group_item = $temp_link_item->getLinkedItem($person);
                            if($group_item->getItemID() == $temp_group_item->getItemID()){
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
            if($current_context->getWorkflowReaderPerson() == '1'){
                foreach($persons_array as $person){
                if (!empty($persons_array[0])){
                        $tmpArray = array();
                        $tmpArray['iid'] = $person->getItemID();
                        $tmpArray['name']=  $person->getFullname();
                        $workflowUserArray[] = $tmpArray;
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

        // mark annotations as readed
        $annotationList = $material->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);
        
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
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();

        
        return $infoArray;
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
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $materialItem = NULL;
        
        if ($item->getItemType() == 'material') {
            // get material from MaterialService
            $materialItem = $materialService->getMaterial($itemId);
            if (!$materialItem) {
                throw $this->createNotFoundException('No material found for id ' . $roomId);
            }
            $formData = $transformer->transform($materialItem);
            $form = $this->createForm('material', $formData, array(
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
            $form = $this->createForm('section', $formData, array());
        }
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $materialItem = $transformer->applyTransformation($materialItem, $form->getData());

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
            'form' => $form->createView(),
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),

        );
    }
    
    /**
     * @Route("/room/{roomId}/material/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
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
            'showWorkflow' => $infoArray['showWorkflow']
        );
    }
        
    /**
     * @Route("/room/{roomId}/material/create")
     * @Template()
     */
    public function createAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $materialData = array();
        $materialService = $this->get('commsy_legacy.material_service');
        $transformer = $this->get('commsy_legacy.transformer.material');
        
        // create new material item
        $materialItem = $materialService->getNewMaterial();
        $materialItem->setTitle('['.$translator->trans('insert title').']');
        $materialItem->setBibKind('none');
        $materialItem->setDraftStatus(1);
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
        $form = $this->createForm('section', $formData, array(
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

        $form = $this->createForm('section');

        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // update title
                $section->setTitle($form->getData()['title']);

                // update modifier
                $section->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $section->save();
                
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
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
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');
        
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
    	        $annotationList =$item->getAnnotationList();
    	        if ( !empty($annotationList) ){
    	            $annotationItem = $annotationList->getFirst();
    	            while($annotationItem){
    	               $noticedManager->markNoticed($annotationItem->getItemID(),'0');
    	               $annotationItem = $annotationList->getNext();
    	            }
    	        }
	        }
	        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('marked %count% entries as read',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'copy') {
           $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'save') {
            $zipfile = $this->download($roomId, $selectedIds);
            $content = file_get_contents($zipfile);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => 'application/zip'));
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'zipfile.zip');   
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
        
        $response = new JsonResponse();
 /*       $response->setData(array(
            'message' => $message,
            'status' => $status
        ));
  */      
        $response->setData(array(
            'message' => $message,
            'timeout' => '5550',
            'layout'   => 'cs-notify-message'
        ));
        return $response;
    }
    
    private function download($roomId, $selectedIds) {
        $itemId = $selectedIds[0];
        
        $detailArray = $this->getDetailInfo($roomId, $itemId);
        $detailArray['roomId'] = $roomId;
        $detailArray['annotationForm'] = $this->createForm('annotation')->createView();
        
        $environment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        global $symfonyContainer;
        $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
        if(!isset($export_temp_folder)) {
            $export_temp_folder = 'var/temp/zip_export';
        }
        $directory_split = explode("/",$export_temp_folder);
        $done_dir = "./";
        foreach($directory_split as $dir) {
            if(!is_dir($done_dir.'/'.$dir)) {
               mkdir($done_dir.'/'.$dir, 0777);
            }
            $done_dir .= '/'.$dir;
        }
        $directory = './'.$export_temp_folder.'/'.time();
        mkdir($directory,0777);
        $filemanager = $environment->getFileManager();
    
        //create HTML-File
        $filename = $directory.'/index.html';
        $handle = fopen($filename, 'a');
        //Put page into string
        $output = $this->render('CommsyBundle:Material:detail.html.twig', $detailArray);
    
        //String replacements
        //$output = str_replace('commsy_print_css.php?cid='.$environment->getCurrentContextID(),'stylesheet.css', $output);
        //$params = $environment->getCurrentParameterArray();

        //copy CSS File
        mkdir($directory.'/css', 0777);
        mkdir($directory.'/css/build', 0777);
        $cssMatchArray = array();
        preg_match_all('~\/css\/build\/commsy[^\.]*.css~', $output, $cssMatchArray);
        foreach ($cssMatchArray as $cssMatch) {
            $tempCssMatch = str_ireplace('/css', 'css', $cssMatch[0]);
            $output = str_ireplace($cssMatch, $tempCssMatch, $output);
            copy($this->get('kernel')->getRootDir().'/../web/'.$tempCssMatch, $directory.'/'.$tempCssMatch);
        }
    
        
        /* //find images in string
        $reg_exp = '~\<a\s{1}href=\"(.*)\"\s{1}t~u';
        preg_match_all($reg_exp, $output, $matches_array);
        $i = 0;
        $iids = array();
    
        if ( !empty($matches_array[1]) ) {
            mkdir($directory.'/images', 0777);
        }
    
        foreach($matches_array[1] as $match) {
            $new = parse_url($matches_array[1][$i],PHP_URL_QUERY);
            parse_str($new,$out);
    
            if(isset($out['amp;iid'])) {
                $index = $out['amp;iid'];
            }
            elseif(isset($out['iid'])) {
                $index = $out['iid'];
            }
            if(isset($index)) {
                $file = $filemanager->getItem($index);
                if ( isset($file) ) {
                    $icon = $directory.'/images/'.$file->getIconFilename();
                    $filearray[$i] = $file->getDiskFileName();
                    if(file_exists(realpath($file->getDiskFileName()))) {
                        include_once('functions/text_functions.php');
                        copy($file->getDiskFileName(),$directory.'/'.toggleUmlaut($file->getFilename()));
                        $output = str_replace($match, toggleUmlaut($file->getFilename()), $output);
                        copy('htdocs/images/'.$file->getIconFilename(),$icon);
    
                        // thumbs gehen nicht
                        // warum nicht allgemeiner mit <img? (siehe unten)
                        // geht unten aber auch nicht
                        $thumb_name = $file->getFilename() . '_thumb';
                        $thumb_disk_name = $file->getDiskFileName() . '_thumb';
                        if ( file_exists(realpath($thumb_disk_name)) ) {
                            copy($thumb_disk_name,$directory.'/images/'.$thumb_name);
                            $output = str_replace($match, $thumb_name, $output);
                        }
                    }
                }
            }
           $i++;
        } */
    
        /* global $c_single_entry_point;
        global $c_commsy_url_path;
        global $c_commsy_domain;
    
        $imgatt_array = array();
        preg_match_all('~\<img\s{1}style=" padding:5px;"\s{1}src=\"(.*)\"\s{1}a~u', $output, $imgatt_array);
         
        $i = 0;
        foreach($imgatt_array[1] as $img) {
            $img = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
            $img = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
            #$img = str_replace($c_single_entry_point.'/','',$img);
            #$img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
            #$img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
            $imgatt_array[1][$i] = str_replace('_thumb.png','',$img);
            foreach($filearray as $fi) {
                $imgname = strstr($fi,$imgatt_array[1][$i]);
                $img = preg_replace('~cid\d{1,}_\d{1,}_~u','',$img);
    
                if($imgname != false) {
                    $disc_manager = $environment->getDiscManager();
                    $disc_manager->setPortalID($environment->getCurrentPortalID());
                    $disc_manager->setContextID($environment->getCurrentContextID());
                    $path_to_file = $disc_manager->getFilePath();
                    unset($disc_manager);
                    $srcfile = $path_to_file.$imgname;
                    $target = $directory.'/'.$img;
                    $size = getimagesize($srcfile);
    
                    $x_orig= $size[0];
                    $y_orig= $size[1];
                    $verhaeltnis = $x_orig/$y_orig;
                    $max_width = 200;
    
                    if ($x_orig > $max_width) {
                       $show_width = $max_width;
                       $show_height = $y_orig * ($max_width/$x_orig);
                    } else {
                       $show_width = $x_orig;
                       $show_height = $y_orig;
                    }
                    switch ($size[2]) {
                        case '1':
                            $im = imagecreatefromgif($srcfile);
                            break;
                        case '2':
                            $im = imagecreatefromjpeg($srcfile);
                            break;
                        case '3':
                            $im = imagecreatefrompng($srcfile);
                            break;
                    }
                    $newimg = imagecreatetruecolor($show_width,$show_height);
                    imagecopyresampled($newimg, $im, 0, 0, 0, 0, $show_width, $show_height, $size[0], $size[1]);
                    imagepng($newimg,$target);
                    imagedestroy($im);
                    imagedestroy($newimg);
                }
            }
           $i++;
        } */
    
        /* // thumbs_new
        preg_match_all('~\<img(.*)src=\"((.*)_thumb.png)\"~u', $output, $imgatt_array);
        foreach($imgatt_array[2] as $img) {
            $img_old = $img;
            $img = str_replace($c_single_entry_point.'/','',$img);
            $img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
            $img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
            $img = mb_substr($img,0,mb_strlen($img)/2);
            $img = preg_replace('~cid\d{1,}_\d{1,}_~u','',$img);
            $output = str_replace($img_old,$img,$output);
        } */
    
        /* $output = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$output);
        $output = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$output);
        $output = preg_replace('~cid\d{1,}_\d{1,}_~u','',$output); */
    
        //write string into file
        fwrite($handle, $output);
        fclose($handle);
        unset($output);

        //create ZIP File
         
        $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.'RUBRIC_NAME'.'_'.$itemId.'.zip';
         
        if(file_exists(realpath($zipfile))) {
            unlink($zipfile);
        }
    
        if ( class_exists('ZipArchive') ) {
            include_once('functions/misc_functions.php');
            $zip = new ZipArchive();
            $filename = $zipfile;
    
            if ( $zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE ) {
                include_once('functions/error_functions.php');
                trigger_error('can not open zip-file '.$filename_zip,E_USER_WARNING);
            }
            $temp_dir = getcwd();
            chdir($directory);
    
            $zip = addFolderToZip('.',$zip);
            chdir($temp_dir);
    
            $zip->close();
            unset($zip);
            //unset($params['downloads']);
        } else {
            include_once('functions/error_functions.php');
            trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNING);
        }
    
        //send zipfile by header
        $translator = $environment->getTranslationObject();
        if($environment->getCurrentModule() == 'announcement'){
            $current_module = $translator->getMessage('ANNOUNCEMENT_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'material'){
            $current_module = $translator->getMessage('MATERIAL_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'date'){
            $current_module = $translator->getMessage('DATE_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'discussion'){
            $current_module = $translator->getMessage('DISCUSSION_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'todo'){
            $current_module = $translator->getMessage('TODO_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'group'){
            $current_module = $translator->getMessage('GROUP_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'topic'){
            $current_module = $translator->getMessage('TOPIC_EXPORT_ITEM_ZIP');
        } elseif($environment->getCurrentModule() == 'user'){
            $current_module = $translator->getMessage('USER_EXPORT_ITEM_ZIP');
        } else {
            $current_module = $environment->getCurrentModule();
        }
    
        $downloadfile = 'RUBRIC_NAME'.'_'.$itemId.'.zip';
    
        return $zipfile;
    }

}