<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Filesystem\Filesystem;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

use CommsyBundle\Form\Type\AnnotationType;

class DownloadService
{
    private $legacyEnvironment;

    private $serviceContainer;
    

    public function __construct(LegacyEnvironment $legacyEnvironment, Container $container)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->serviceContainer = $container;
    }

    public function zipFile($roomId, $itemIds)
    {
        error_log(print_r($itemIds, true));
        
        $environment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();

        $exportTempFolder = $this->serviceContainer->getParameter('kernel.root_dir') . '/../files/temp/zip_export/' . time();

        $fileSystem = new Filesystem();

        try {
            $fileSystem->mkdir($exportTempFolder, 0777);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating a directory at " . $e->getPath();
        }
        $directory = $exportTempFolder;

        $filemanager = $environment->getFileManager();
    
        if (!is_array($itemIds)) {
            $itemIds = array($itemIds);
        }
    
        foreach ($itemIds as $itemId) {
            $detailArray = $this->getDetailInfo($roomId, $itemId);
            $detailArray['roomId'] = $roomId;
            $detailArray['annotationForm'] = $this->serviceContainer->get('form.factory')->create(AnnotationType::class)->createView();
            
            $tempDirectory = $directory.'/'.$detailArray['item']->getTitle();
            $index = 1;
            while (file_exists($tempDirectory)) {
                $tempDirectory = $directory.'/'.$detailArray['item']->getTitle().'_'.$index;    
            }
            mkdir($tempDirectory);
            
            // create PDF-file
            $output = $this->serviceContainer->get('templating')->renderResponse('CommsyBundle:Material:detailPrint.html.twig', $detailArray);
            $pdf = $this->serviceContainer->get('knp_snappy.pdf')->getOutputFromHtml($output);
            file_put_contents($tempDirectory.'/test.pdf', $pdf);
        
            // add files
            $files = array();
            if ($detailArray['item']->getItemType() == 'material') {
                $files = $detailArray['item']->getFileListWithFilesFromSections()->to_array();
            }
            $filesCounter = array();
            if (!empty($files)) {
                mkdir($tempDirectory.'/files', 0777);
                foreach ($files as $file) {
                    if (file_exists($this->serviceContainer->get('kernel')->getRootDir().'/'.$file->getDiskFileName())) {
                        if (!file_exists($tempDirectory.'/files/'.$file->getFilename())) {
                            copy($this->serviceContainer->get('kernel')->getRootDir().'/'.$file->getDiskFileName(), $tempDirectory.'/files/'.$file->getFilename());
                        } else {
                            $fileNameWithoutExtension = mb_substr($file->getFilename(), 0, strlen($file->getFilename())-(strlen($file->getExtension())+1));
                            
                            $counter = 1;
                            if (isset($filesCounter[$fileNameWithoutExtension])) {
                                $filesCounter[$fileNameWithoutExtension] = $filesCounter[$fileNameWithoutExtension] + 1;
                                $counter = $filesCounter[$fileNameWithoutExtension];
                            } else {
                                $filesCounter[$fileNameWithoutExtension] = $counter;
                            }
                            
                            $newFilename = $fileNameWithoutExtension.' ('.$counter.').'.$file->getExtension();
                            copy($this->serviceContainer->get('kernel')->getRootDir().'/'.$file->getDiskFileName(), $tempDirectory.'/files/'.$newFilename);
                        }
                    }
                }
            }
        }

        //create ZIP File
        $zipfile = $exportTempFolder.DIRECTORY_SEPARATOR.'RUBRIC_NAME'.'_'.$itemId.'.zip';
         
        if(file_exists(realpath($zipfile))) {
            unlink($zipfile);
        }

        include_once('functions/misc_functions.php');
        $zip = new \ZipArchive();
        $filename = $zipfile;

        if ( $zip->open($filename, \ZipArchive::CREATE) !== TRUE ) {
            include_once('functions/error_functions.php');
            trigger_error('can not open zip-file '.$filename,E_USER_WARNING);
        }
        $temp_dir = getcwd();
        chdir($directory);

        $zip = addFolderToZip('.',$zip);
        chdir($temp_dir);

        $zip->close();
        unset($zip);
    
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
    
    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $itemService = $this->serviceContainer->get('commsy.item_service');
        $item = $itemService->getTypedItem($itemId);
        error_log(print_r($itemId, true));
        
        if ($item->getItemType() == 'material') {
            $materialService = $this->serviceContainer->get('commsy_legacy.material_service');
    
            $annotationService = $this->serviceContainer->get('commsy_legacy.annotation_service');
            
            $material = $materialService->getMaterial($itemId);
            if($material == null) {
                $section = $materialService->getSection($itemId);
                $material = $materialService->getMaterial($section->getLinkedItemID());
    
            }
            
            $sectionList = $material->getSectionList()->to_array();
            
            $itemArray = array($material);
            $itemArray = array_merge($itemArray, $sectionList);
    
            $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();
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
            $readerService = $this->serviceContainer->get('commsy.reader_service');
            
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
                $assessmentService = $this->serviceContainer->get('commsy_legacy.assessment_service');
                $ratingDetail = $assessmentService->getRatingDetail($material);
                $ratingAverageDetail = $assessmentService->getAverageRatingDetail($material);
                $ratingOwnDetail = $assessmentService->getOwnRatingDetail($material);
            }
    
            $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();
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
    
    
            $infoArray['item'] = $material;
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
            
        } else if ($item->getItemType() == 'date') {
            $dateService = $this->serviceContainer->get('commsy_legacy.date_service');
            $date = $dateService->getDate($itemId);
            
            $infoArray['item'] = $date;
        }
        
        return $infoArray;
    }
}