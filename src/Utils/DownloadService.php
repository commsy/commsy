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

namespace App\Utils;

use App\Form\Type\AnnotationType;
use App\Services\LegacyEnvironment;
use App\Services\PrintService;
use cs_environment;
use cs_item;
use cs_section_item;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;
use ZipArchive;

class DownloadService
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly PrintService $printService,
        private readonly ItemService $itemService,
        private readonly MaterialService $materialService,
        private readonly ReaderService $readerService,
        private readonly AnnotationService $annotationService,
        private readonly AssessmentService $assessmentService,
        private readonly FormFactoryInterface $formFactory,
        private readonly Environment $environment,
        private readonly ParameterBagInterface $parameterBag,
        private readonly CategoryService $categoryService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function zipFile($roomId, $itemIds)
    {
        $itemIds = is_array($itemIds) ? $itemIds : [$itemIds];

        $exportTempFolder = $this->parameterBag->get('kernel.project_dir').'/files/temp/zip_export/'.time();

        $fileSystem = new Filesystem();

        try {
            $fileSystem->mkdir($exportTempFolder, 0777);
        } catch (IOException $e) {
            echo 'An error occurred while creating a directory at '.$e->getPath();
        }
        $directory = $exportTempFolder;

        foreach ($itemIds as $itemId) {
            $detailArray = $this->getDetailInfo($roomId, $itemId);
            $detailArray['roomId'] = $roomId;
            $detailArray['annotationForm'] = $this->formFactory->create(AnnotationType::class)->createView();

            $item = $detailArray['item'];

            $tempDirectory = $directory.'/'.$item->getTitle();
            $index = 1;
            while (file_exists($tempDirectory)) {
                $tempDirectory = $directory.'/'.$item->getTitle().'_'.$index;
                ++$index;
            }
            $fileSystem->mkdir($tempDirectory, 0777);

            // create PDF-file
            $htmlView = $item->getItemType().'/detail_print.html.twig';
            $htmlOutput = $this->environment->render($htmlView, $detailArray);
            if (str_contains($htmlOutput, 'localhost:81')) { // local fix for wkhtmltopdf
                $htmlOutput = preg_replace("/<img[^>]+\>/i", '(image) ', $htmlOutput);
            }
            file_put_contents($tempDirectory.'/'.$item->getTitle().'.pdf', $this->printService->getPdfContent($htmlOutput));

            // add files
            $this->copyItemFilesToFolder($item, $tempDirectory.'/files/');
        }

        // create ZIP File
        $zipFile = $exportTempFolder.'/'.time().'.zip';

        if (file_exists(realpath($zipFile))) {
            unlink($zipFile);
        }

        include_once 'functions/misc_functions.php';
        $zip = new ZipArchive();
        $filename = $zipFile;

        if (true !== $zip->open($filename, ZipArchive::CREATE)) {
            throw new Exception("can not open zip-file $filename");
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
     * @param cs_item $item         The CommSy item
     * @param string   $targetFolder Path to the target folder
     */
    private function copyItemFilesToFolder($item, $targetFolder)
    {
        $files = $this->itemService->getItemFileList($item->getItemId());

        if (!empty($files)) {
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($targetFolder, 0777);

            $filesCounter = [];
            foreach ($files as $file) {
                $sourceFilePath = $file->getDiskFileName();
                if ($fileSystem->exists($sourceFilePath)) {
                    $targetFilePath = $targetFolder.'/'.$file->getFilename();

                    if (!$fileSystem->exists($targetFilePath)) {
                        $fileSystem->copy($sourceFilePath, $targetFilePath);
                    } else {
                        $fileNameWithoutExtension = mb_substr((string) $file->getFilename(), 0, strlen((string) $file->getFilename()) - (strlen((string) $file->getExtension()) + 1));

                        $counter = 1;
                        if (isset($filesCounter[$fileNameWithoutExtension])) {
                            $filesCounter[$fileNameWithoutExtension] = $filesCounter[$fileNameWithoutExtension] + 1;
                            $counter = $filesCounter[$fileNameWithoutExtension];
                        } else {
                            $filesCounter[$fileNameWithoutExtension] = $counter;
                        }

                        $newFilename = $fileNameWithoutExtension.' ('.$counter.').'.$file->getExtension();
                        $fileSystem->copy($sourceFilePath, $targetFolder.'/'.$newFilename);
                    }
                }
            }
        }
    }

    private function getDetailInfo($roomId, $itemId)
    {
        $infoArray = [];

        $itemService = $this->itemService;
        $item = $itemService->getTypedItem($itemId);

        $itemArray = [$item];

        if ('material' == $item->getItemType()) {
            $sectionList = $item->getSectionList()->to_array();
            $itemArray = array_merge($itemArray, $sectionList);
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readCountDescription = $this->readerService->getReadCountDescriptionForItem($item);

        $readerList = [];
        $modifierList = [];
        foreach ($itemArray as $tempItem) {
            $readerList[$tempItem->getItemId()] = $this->readerService->getStatusForItem($item)->value;
            $modifierList[$tempItem->getItemId()] = $itemService->getAdditionalEditorsForItem($tempItem);
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
                if ($tempMaterial->getItemID() == $item->getItemID()) {
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

        $userManager = $this->legacyEnvironment->getUserManager();

        if ($current_context->withWorkflowReader()) {
            $itemManager = $this->legacyEnvironment->getItemManager();
            $users_read_array = $itemManager->getUsersMarkedAsWorkflowReadForItem($item->getItemID());
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
                    if (!$currentUserItem->isGuest() && $item->isReadByUser($currentUserItem)) {
                        $workflowUnread = true;
                    } else {
                        $workflowRead = true;
                    }
                }
            }
        }

        $workflowText = '';
        if ($current_context->withWorkflow()) {
            $workflowText = match ($item->getWorkflowTrafficLight()) {
                '0_green' => $current_context->getWorkflowTrafficLightTextGreen(),
                '1_yellow' => $current_context->getWorkflowTrafficLightTextYellow(),
                '2_red' => $current_context->getWorkflowTrafficLightTextRed(),
                default => '',
            };
        }

        $ratingDetail = [];
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $this->assessmentService->getRatingDetail($item);
            $ratingAverageDetail = $this->assessmentService->getAverageRatingDetail($item);
            $ratingOwnDetail = $this->assessmentService->getOwnRatingDetail($item);
        }

        // $item = $material;
        $this->readerService->markItemAsRead($item);

        // mark annotations as read
        $annotationList = $item->getAnnotationList();
        $this->annotationService->markAnnotationsReadedAndNoticed($annotationList);

        if ('material' == $item->getItemType()) {
            $sections = $item->getSectionList();
            foreach ($sections as $section) {
                /** @var cs_section_item $section */
                $this->readerService->markItemAsRead($section);
            }
        }

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $this->categoryService->getTags($roomId);
            $dateCategories = $item->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $dateCategories);
        }

        $articleList = [];
        if ('discussion' == $item->getItemType()) {
            $articleList = $item->getAllArticles()->to_array();
        }

        $members = [];
        if ('group' == $item->getItemType()) {
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
        $infoArray['readCount'] = $readCountDescription->getReadTotal();
        $infoArray['readSinceModificationCount'] = $readCountDescription->getReadSinceModification();
        $infoArray['userCount'] = $readCountDescription->getUserTotal();
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

        if ('material' == $item->getItemType()) {
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

    private function getTagDetailArray($baseCategories, $itemCategories)
    {
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
}
