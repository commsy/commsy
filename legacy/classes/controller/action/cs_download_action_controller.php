<?php
	require_once('classes/controller/cs_base_controller.php');
	
	class cs_download_action_controller extends cs_base_controller {
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'download_action';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		*/
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}
		
		public function actionAction() {
			global $symfonyContainer;

			/************************************************************************************
			 * This will generate the downloadable content
			************************************************************************************/
			$currentContext = $this->_environment->getCurrentContextItem();
			$currentUser = $this->_environment->getCurrentUserItem();
			
			// get item
			$itemId = $_GET["iid"];
			$itemManager = $this->_environment->getItemManager();
			$item = $itemManager->getItem($itemId);
			$type = $item->getItemType();
			$manager = $this->_environment->getManager($type);
			
			// get export temp folder
			$export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
			if (!isset($export_temp_folder)) {
				$export_temp_folder = "var/temp/zip_export";
			}
			$exportTempFolder = $export_temp_folder;
			
			// create directory structure if needed
			$directorySplit = explode("/", $exportTempFolder);
			$doneDir = "./";
			
			foreach ($directorySplit as $dir) {
				if (!is_dir($doneDir . "/" . $dir)) {
					mkdir($doneDir . "/" . $dir, 0777);
				}
				
				$doneDir .= "/" . $dir;
			}
			
			$directory = "./" . $exportTempFolder . "/" . uniqid("", true);
			mkdir($directory, 0777);
			
			// material version specific
			if ($type === CS_MATERIAL_TYPE && isset($_GET["versionId"])) {
				$item = $manager->getItemByVersion($itemId, $_GET["versionId"]);
			} else {
				$item = $manager->getItem($itemId);
			}
			
			// check access
			if(	($currentContext->isProjectRoom() && $currentContext->isClosed()) ||
					($itemId === "NEW") ||
					(isset($item) && !$item->maySee($currentUser))) {
					
				return;
			}
			
			// init needed values
			$cid = $this->_environment->getCurrentContextID();
			$fct = "detail";

			// label item
			if($type == 'label') {
				$mod = $item->getLabelType();
			} else {
				$mod = $type;
			}
			
			/************************************************************************************
			 * We need to create a new instance of smarty and set some environment variables
			 * to load the detail page separatly
			************************************************************************************/
			
			// set output mode
			$this->_environment->setOutputMode("print");
			
			// get a new smarty instance - this is copied from commsy.php
			require_once('classes/cs_smarty.php');

			$c_theme = $symfonyContainer->getParameter('commsy.themes.default');
			if(!isset($c_theme) || empty($c_theme)) $c_theme = 'default';
			
			// room theme
			$color = $this->_environment->getCurrentContextItem()->getColorArray();
			$theme = $color['schema'];
			
			if($theme !== 'default') {
				$c_theme = $theme;
			}
			$smarty = new cs_smarty($this->_environment, $c_theme);
			$this->_environment->setTemplateEngine($smarty);
			
			// setup controller
			$controller_name = 'cs_' . $mod . '_' . $fct. '_controller';
			require_once('classes/controller/' . $fct . '/' . $controller_name . '.php');
			
			// invoke module and function
			$this->_environment->setCurrentModule($mod);
			$this->_environment->setCurrentFunction($fct);
			
			$controller = new $controller_name($this->_environment);
			$controller->processTemplate();
			
			// write output in buffer and fetch
			ob_start();
			$controller->displayTemplate();
			$output = ob_get_clean();
			
			// create HTML-File
			$fileName = $directory . "/index.html";
			$fileHandle = fopen($fileName, "a");
			
			/************************************************************************************
			 * Next step is to adjust the html output for the zip package and copy all
			 * the needed files to temporary export folder
			************************************************************************************/
			// create folder
			mkdir($directory . "/css", 0777);
			mkdir($directory . "/images", 0777);
			mkdir($directory . "/files", 0777);
			
			// get all linked css paths
			$linkedCSS = array();
			preg_match_all("=<link.*?href\=\"(.*?)\"=", $output, $matches);
			list($matches, $linkedCSS) = $matches;
			
			foreach($linkedCSS as $css) {
				// extract css filename
				$fileName = basename($css);
				
				// prepend a unique id to all file names
				$newLinkedCSS = "css/" . uniqid("", true) . $fileName;
				
				// replace links in HTML output
				$output = str_replace($css, $newLinkedCSS, $output);
				
				// copy css files
				copy("htdocs/" . $css, $directory . "/" . $newLinkedCSS);
			}
			
			// get all images
			$images = array();
			preg_match_all("=<img.*?src\=\"(.*?)\"=", $output, $matches);
			list($matches, $images) = $matches;
			
			foreach($images as $image) {
				// extract image filename
				$fileName = basename($image);
				
				$newImage = "images/" . $fileName;
				
				// replace links in HTML output
				$output = str_replace($image, $newImage, $output);
				
				// copy images
				copy("htdocs/" . $image, $directory . "/" . $newImage);
			}
			
			// TODO: getimage, etc...
			
			// get files
			if ($type === CS_MATERIAL_TYPE) {
				$fileList = $item->getFileListWithFilesFromSections();
			} elseif ($type === CS_DISCUSSION_TYPE) {
				$fileList = $item->getFileListWithFilesFromArticles();
			} elseif ($type === CS_TODO_TYPE) {
				$fileList = $item->getFileListWithFilesFromSteps();
			} else {
				$fileList = $item->getFileList();
			}
			
			$file = $fileList->getFirst();
			while ($file) {
				// copy files
				copy($file->getDiskFileName(), $directory . "/files/" . $file->getFileName());
				
				$file = $fileList->getNext();
			}
			
			$files = array();
			preg_match_all("=<a.*?href\=\"commsy\.php/(.*?)\?.*\"=", $output, $matches);
			list($matches, $files) = $matches;
			
			foreach ($files as $file) {
				$newFile = "files/" . $file;
				
				$output = preg_replace("=\"commsy\.php/" . $file . ".*?\"=", "\"" . $newFile . "\"", $output);
			}
			
			// write output to file
			fwrite($fileHandle, $output);
			fclose($fileHandle);
			
			/************************************************************************************
			 * All files are ready now, create a ZIP archive and set headers for downloading
			************************************************************************************/
			// create zip file
			$zipFile = $exportTempFolder . DIRECTORY_SEPARATOR . $mod . "_" . $itemId . ".zip";
			
			if (file_exists(realpath($zipFile))) unlink($zipFile);
			
			if (class_exists("ZipArchive")) {
				include_once('functions/misc_functions.php');
				
				$zipArchive = new ZipArchive();
				
				if ($zipArchive->open($zipFile, ZIPARCHIVE::CREATE) !== TRUE) {
					include_once('functions/error_functions.php');
					trigger_error('can not open zip-file ' . $zipFile, E_USER_WARNING);
				}
				
				$tempDir = getcwd();
				chdir($directory);
				$zipArchive = addFolderToZip(".", $zipArchive);
				chdir($tempDir);
				
				$zipArchive->close();
			} else {
				include_once('functions/error_functions.php');
				trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNING);
			}
			
			// send zipfile by header
			$translator = $this->_environment->getTranslationObject();
			if($mod == 'announcement'){
				$current_module = $translator->getMessage('ANNOUNCEMENT_EXPORT_ITEM_ZIP');
			} elseif($mod == 'material'){
				$current_module = $translator->getMessage('MATERIAL_EXPORT_ITEM_ZIP');
			} elseif($mod == 'date'){
				$current_module = $translator->getMessage('DATE_EXPORT_ITEM_ZIP');
			} elseif($mod == 'discussion'){
				$current_module = $translator->getMessage('DISCUSSION_EXPORT_ITEM_ZIP');
			} elseif($mod == 'todo'){
				$current_module = $translator->getMessage('TODO_EXPORT_ITEM_ZIP');
			} elseif($mod == 'group'){
				$current_module = $translator->getMessage('GROUP_EXPORT_ITEM_ZIP');
			} elseif($mod == 'topic'){
				$current_module = $translator->getMessage('TOPIC_EXPORT_ITEM_ZIP');
			} elseif($mod == 'user'){
				$current_module = $translator->getMessage('USER_EXPORT_ITEM_ZIP');
			} else {
				$current_module = $mod;
			}
			
			$downloadFile = $current_module . "_" . $itemId . ".zip";
			
			header('Content-type: application/zip');
		    header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
		    readfile($zipFile);
			
			$fileManager = $this->_environment->getFileManager();
		}
	}
?>
