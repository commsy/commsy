<?php

require_once('classes/controller/cs_base_controller.php');

class cs_export_export_controller extends cs_base_controller
{
    public function __construct(cs_environment $environment)
    {
        // call parent
        parent::__construct($environment);

        $this->_tpl_file = 'download_action';
    }

    /**
     * every derived class needs to implement an processTemplate function
     */
    public function processTemplate()
    {
        // call parent
        parent::processTemplate();
    }

    public function actionExport()
    {
        $currentContext = $this->_environment->getCurrentContextItem();
        $currentUser = $this->_environment->getCurrentUserItem();
        $translator = $this->_environment->getTranslationObject();

        $access = false;
        if ($currentContext->getItemID() === $currentUser->getOwnRoom()->getItemID()) {
            $access = true;
        }

        if ($currentContext->isArchived() && $currentUser->isModerator()) {
            $access = true;
        }

        if (!$access) {
            die('access denied!');
        }

        $exportFolder = 'var/temp/zip_export/' . uniqid('', true);

        // create if not existent
        if (!is_dir($exportFolder)) {
            mkdir($exportFolder, 0777, true);
        }

        // get all rubrics with downloadable content
        if ($currentContext->isPrivateRoom()) {
            $rubrics = ['date', 'material', 'discussion', 'todo'];
        } else {
            $rubrics = array_filter($currentContext->getAvailableRubrics(), function($rubric) {
                return in_array($rubric, [
                    'announcement',
                    'date',
                    'material',
                    'discussion',
                    'topic',
                    'todo'
                ]);
            });
        }

        foreach ($rubrics as $rubric) {
            $manager = $this->_environment->getManager($rubric);
            $manager->setContextLimit($currentContext->getItemID());

            $translatedRubric = $translator->getMessage('COMMON_' . strtoupper($rubric) . '_INDEX');

            // create a new rubric folder
            $rubricFolder = $exportFolder . '/' . $translatedRubric;
            mkdir($rubricFolder);

            if ($rubric == 'date') {
                $manager->setWithoutDateModeLimit();
            }

            $manager->select();
            $itemList = $manager->get();

            $item = $itemList->getFirst();
            while ($item) {
                // get related files
                switch ($rubric) {
                    case 'material':
                        $fileList = $item->getFileListWithFilesFromSections();
                        break;
                    case 'discussion':
                        $fileList = $item->getFileListWithFilesFromArticles();
                        break;
                    case 'todo':
                        $fileList = $item->getFileListWithFilesFromSteps();
                        break;
                    default:
                        $fileList = $item->getFileList();
                }

                if (!$fileList->isEmpty()) {
                    // create a new item folder
                    $itemFolder = $rubricFolder . '/' . $item->getTitle();
                    mkdir($itemFolder);

                    $file = $fileList->getFirst();
                    while ($file) {
                        $filePath = $itemFolder . '/' . $file->getFileName();
                        copy($file->getDiskFileName(), $filePath);

                        $file = $fileList->getNext();
                    }
                }

                $item = $itemList->getNext();
            }
        }

        // create a new archive
        require_once('functions/misc_functions.php');
        $zipFile = $exportFolder . '/' . $currentContext->getTitle() . '.zip';

        if (file_exists(realpath($zipFile))) {
            unlink($zipFile);
        }

        $zipArchive = new ZipArchive();

        if (!$zipArchive->open($zipFile, ZipArchive::CREATE)) {
            die ("error");
        }

        $tempDir = getcwd();
        chdir($exportFolder);
        $zipArchive = addFolderToZip(".", $zipArchive);
        chdir($tempDir);

        $zipArchive->close();

        foreach (new DirectoryIterator($exportFolder) as $file) {
            if (!$file->isDot() && $file->isDir()) {
                $this->deleteSub($file->getPathname());
                rmdir($file->getPathname());
            }
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zipFile));
        $this->readChunks($zipFile);
    }

    /**
     * Reads and delivers a file chunk-wise to keep
     * memory footprint low
     *
     * @param $fileName Name of the file
     */
    private function readChunks($fileName)
    {
        if (is_file($fileName)) {
            $chunkSize = 1024 * 1024;
            $handle = fopen($fileName, 'rb');

            while (!feof($handle)) {
                $buffer = fread($handle, $chunkSize);
                echo $buffer;
                ob_flush();
                flush();
            }

            fclose($handle);
        }
    }

    private function deleteSub($directory) {
        foreach (new DirectoryIterator($directory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isDir()) {
                $this->deleteSub($fileInfo->getPathname());
                rmdir($fileInfo->getPathname());
            }

            if ($fileInfo->isFile()) {
                unlink($fileInfo->getPathname());
            }
        }
    }
}