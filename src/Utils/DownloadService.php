<?php
namespace App\Utils;

use App\Form\Type\AnnotationType;
use App\Services\LegacyEnvironment;
use App\Services\PrintService;
use cs_environment;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DownloadService
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Container
     */
    private Container $serviceContainer;

    /**
     * @var PrintService
     */
    private PrintService $printService;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var MaterialService
     */
    private MaterialService $materialService;

    /**
     * @var ReaderService
     */
    private ReaderService $readerService;

    /**
     * @var AnnotationService
     */
    private AnnotationService $annotationService;

    /**
     * @var AssessmentService
     */
    private AssessmentService $assessmentService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Container $container,
        PrintService $printService,
        ItemService $itemService,
        MaterialService $materialService,
        ReaderService $readerService,
        AnnotationService $annotationService,
        AssessmentService $assessmentService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->serviceContainer = $container;
        $this->printService = $printService;
        $this->itemService = $itemService;
        $this->materialService = $materialService;
        $this->readerService = $readerService;
        $this->annotationService = $annotationService;
        $this->assessmentService = $assessmentService;
    }

    public function zipFile($roomId, $itemIds)
    {
        $itemIds = is_array($itemIds) ? $itemIds : [$itemIds];

        $exportTempFolder = $this->serviceContainer->getParameter('kernel.root_dir') . '/../files/temp/zip_export/' . time();

        $fileSystem = new Filesystem();

        try {
            $fileSystem->mkdir($exportTempFolder, 0777);
        } catch (IOException $e) {
            echo "An error occurred while creating a directory at " . $e->getPath();
        }
        $directory = $exportTempFolder;
    
        foreach ($itemIds as $itemId) {
            $detailArray = $this->getDetailInfo($roomId, $itemId);
            $detailArray['roomId'] = $roomId;
            $detailArray['annotationForm'] = $this->serviceContainer->get('form.factory')->create(AnnotationType::class)->createView();

            $item = $detailArray['item'];

            $tempDirectory = $directory . '/' . $item->getTitle();
            $index = 1;
            while (file_exists($tempDirectory)) {
                $tempDirectory = $directory . '/' . $item->getTitle() . '_' . $index;
                $index++;
            }
            $fileSystem->mkdir($tempDirectory, 0777);
            
            // create PDF-file
            $htmlView = $item->getItemType() . '/detail_print.html.twig';
            $htmlOutput = $this->serviceContainer->get('templating')->renderResponse($htmlView, $detailArray);
            if (str_contains($htmlOutput->getContent(),"localhost:81")) { // local fix for wkhtmltopdf
                $htmlOutput = preg_replace("/<img[^>]+\>/i", "(image) ", $htmlOutput);
            }
            file_put_contents($tempDirectory . '/' . $item->getTitle() . '.pdf', $this->printService->getPdfContent($htmlOutput));

            // add files
            $this->copyItemFilesToFolder($item, $tempDirectory . '/files/');
        }

        // create ZIP File
        $zipFile = $exportTempFolder . '/' . time() . '.zip';
         
        if (file_exists(realpath($zipFile))) {
            unlink($zipFile);
        }

        include_once('functions/misc_functions.php');
        $zip = new \ZipArchive();
        $filename = $zipFile;

        if ( $zip->open($filename, \ZipArchive::CREATE) !== TRUE ) {
            include_once('functions/error_functions.php');
            trigger_error('can not open zip-file '.$filename,E_USER_WARNING);
        }
        $temp_dir = getcwd();
        chdir($directory);

        $zip = addFolderToZip('.', $zip);
        chdir($temp_dir);

        $zip->close();
    
        return $zipFile;
    }

    /**
     * Copies item files into a target folder for zip generation. Takes also duplicate file names into account.
     *
     * @param cs_item $item The CommSy item
     * @param string $targetFolder Path to the target folder
     */
    private function copyItemFilesToFolder($item, $targetFolder)
    {
        $files = $this->itemService->getItemFileList($item->getItemId());

        if (!empty($files)) {
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($targetFolder, 0777);

            $rootDirectory = $this->serviceContainer->get('kernel')->getRootDir();

            $filesCounter = [];
            foreach ($files as $file) {
                $sourceFilePath = $rootDirectory . '/' . $file->getDiskFileName();
                if ($fileSystem->exists($sourceFilePath)) {
                    $targetFilePath = $targetFolder . '/' . $file->getFilename();

                    if (!$fileSystem->exists($targetFilePath)) {
                        $fileSystem->copy($sourceFilePath, $targetFilePath);
                    } else {
                        $fileNameWithoutExtension = mb_substr($file->getFilename(), 0, strlen($file->getFilename())-(strlen($file->getExtension())+1));

                        $counter = 1;
                        if (isset($filesCounter[$fileNameWithoutExtension])) {
                            $filesCounter[$fileNameWithoutExtension] = $filesCounter[$fileNameWithoutExtension] + 1;
                            $counter = $filesCounter[$fileNameWithoutExtension];
                        } else {
                            $filesCounter[$fileNameWithoutExtension] = $counter;
                        }

                        $newFilename = $fileNameWithoutExtension . ' (' . $counter . ').' . $file->getExtension();
                        $fileSystem->copy($sourceFilePath, $targetFolder . '/' . $newFilename);
                    }
                }
            }
        }
    }
    
    private function getDetailInfo($roomId, $itemId)
    {
        $infoArray = array();
        
        $itemService = $this->serviceContainer->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);
        
        $itemArray = array($item);
        
        if ($item->getItemType() == 'material') {
            $sectionList = $item->getSectionList()->to_array();
            $itemArray = array_merge($itemArray, $sectionList);
        }

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
        while ( $current_user ) {
		   $id_array[] = $current_user->getItemID();
		   $current_user = $user_list->getNext();
		}
		$readerManager->getLatestReaderByUserIDArray($id_array,$item->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($item->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $item->getModificationDate() ) {
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
        foreach ($itemArray as $tempItem) {
            $reader = $this->readerService->getLatestReader($tempItem->getItemId());
            if ( empty($reader) ) {
               $readerList[$tempItem->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $tempItem->getModificationDate() ) {
               $readerList[$tempItem->getItemId()] = 'changed';
            }
            $modifierList[$tempItem->getItemId()] = $itemService->getAdditionalEditorsForItem($tempItem);
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
                if ($tempMaterial->getItemID() == $item->getItemID()) {
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
            $itemManager = $this->legacyEnvironment->getItemManager();
            $users_read_array = $itemManager->getUsersMarkedAsWorkflowReadForItem($item->getItemID());
            $persons_array = array();
            foreach($users_read_array as $user_read){
                $persons_array[] = $userManager->getItem($user_read['user_id']);
            }

            if($current_context->getWorkflowReaderGroup() == '1'){
                $group_manager = $this->legacyEnvironment->getGroupManager();
                $group_manager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
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

            $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();
            $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();
            
            if ($currentContextItem->withWorkflow()) {
                if (!$currentUserItem->isRoot()) {
                    if (!$currentUserItem->isGuest() && $item->isReadByUser($currentUserItem)) {
                        $workflowUnread = true;
                    } else  {
                        $workflowRead = true;
                    }
                }
            }
        }

        $workflowText = '';
        if ($current_context->withWorkflow()) {
            switch ($item->getWorkflowTrafficLight()) {
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
            $ratingDetail = $this->assessmentService->getRatingDetail($item);
            $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($item);
            $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($item);
        }

        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $noticed_manager = $this->legacyEnvironment->getNoticedManager();

        //$item = $material;
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        // mark annotations as read
        $annotationList = $item->getAnnotationList();
        $this->annotationService->markAnnotationsReadedAndNoticed($annotationList);
 
        if ($item->getItemType() == 'material') {
            $readsectionList = $item->getSectionList();
    
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
        }

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->serviceContainer->get('commsy_legacy.category_service')->getTags($roomId);
            $dateCategories = $item->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        $articleList = [];
        if ($item->getItemType() == 'discussion') {
            $articleList = $item->getAllArticles()->to_array();
        }

        $members = [];
        if ($item->getItemType() == 'group') {
            $members = $item->getMemberItemList()->to_array();
        }

        $infoArray['item'] = $item;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($materials);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['ratingArray'] = $current_context->isAssessmentActive() ? [
            'ratingDetail' => $ratingDetail,
            'ratingAverageDetail' => $ratingAverageDetail,
            'ratingOwnDetail' => $ratingOwnDetail,
        ] : [];
        $infoArray['roomCategories'] = $categories;
        $infoArray['articleList'] = $articleList;
        $infoArray['members'] = $members;
        
        if ($item->getItemType() == 'material') {
            $infoArray['sectionList'] = $sectionList;
            $infoArray['materialList'] = $materialList;                
            $infoArray['workflowGroupArray'] = $workflowGroupArray;
            $infoArray['workflowUserArray'] = $workflowUserArray;
            $infoArray['workflowText'] = $workflowText;
            $infoArray['workflowValidityDate'] = $item->getWorkflowValidityDate();
            $infoArray['workflowResubmissionDate'] = $item->getWorkflowResubmissionDate();
            $infoArray['workflowUnread'] = $workflowUnread;
            $infoArray['workflowRead'] = $workflowRead;
            $infoArray['showRating'] = $current_context->isAssessmentActive();
            $infoArray['showWorkflow'] = $current_context->withWorkflow();
        }
                
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
}