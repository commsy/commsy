<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Filesystem\Filesystem;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class DownloadService
{
    private $legacyEnvironment;

    private $serviceContainer;
    

    public function __construct(LegacyEnvironment $legacyEnvironment, Container $container)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->serviceContainer = $container;
    }

    public function zipFile($roomId, $itemId)
    {
        //$itemId = $selectedIds[0];
        
        $detailArray = $this->getDetailInfo($roomId, $itemId);
        $detailArray['roomId'] = $roomId;
        $detailArray['annotationForm'] = $this->serviceContainer->get('form.factory')->create('annotation')->createView();
        
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
    
        //create HTML-File
        $filename = $directory.'/index.html';
        $handle = fopen($filename, 'a');
        //Put page into string
        $output = $this->serviceContainer->get('templating')->renderResponse('CommsyBundle:Material:detail.html.twig', $detailArray);
    
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
            copy($this->serviceContainer->get('kernel')->getRootDir().'/../web/'.$tempCssMatch, $directory.'/'.$tempCssMatch);
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
        
        $materialService = $this->serviceContainer->get('commsy_legacy.material_service');
        $itemService = $this->serviceContainer->get('commsy.item_service');

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
        
        return $infoArray;
    }
}