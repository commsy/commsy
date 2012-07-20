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
		
		/*
		 * function getCSS ( $file, $file_url ) {
   $out = fopen($file,'wb');
   if ( $out == false ) {
      include_once('functions/error_functions.php');
      trigger_error('can not open destination file. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
   }
   if ( function_exists('curl_init') ) {
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_FILE,$out);
      curl_setopt($ch,CURLOPT_HEADER,0);
      curl_setopt($ch,CURLOPT_URL,$file_url);
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
      curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
      global $c_proxy_ip;
      global $c_proxy_port;
      if ( !empty($c_proxy_ip) ) {
         $proxy = $c_proxy_ip;
         if ( !empty($c_proxy_port) ) {
            $proxy = $c_proxy_ip.':'.$c_proxy_port;
         }
         curl_setopt($ch,CURLOPT_PROXY,$proxy);
      }
      curl_exec($ch);
      $error = curl_error($ch);
      if ( !empty($error) ) {
         include_once('functions/error_functions.php');
         trigger_error('curl error: '.$error.' - '.$file_url.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
      curl_close($ch);
   } else {
      include_once('functions/error_functions.php');
      trigger_error('curl library php5-curl is not installed - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
   }
   fclose($out);
}
		 */
		
		public function actionAction() {
			/************************************************************************************
			 * This will generate the downloadable content
			************************************************************************************/
			
			// get export temp folder
			global $export_temp_folder;
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
			
			$directory = "./" . $exportTempFolder . "/" . time();
			mkdir($directory, 0777);
			
			// get item
			$itemId = $_GET["iid"];
			$itemManager = $this->_environment->getItemManager();
			$item = $itemManager->getItem($itemId);
			$module = $item->getItemType();
			
			// init needed values
			$cid = $this->_environment->getCurrentContextID();
			$mod = $module;
			$fct = "detail";
			
			/************************************************************************************
			 * We need to create a new instance of smarty and set some environment variables
			 * to load the detail page separatly
			************************************************************************************/
			
			// set output mode
			$this->_environment->setOutputMode("print");
			
			// get a new smarty instance - this is copied from commsy.php
			require_once('classes/cs_smarty.php');
			global $c_theme;
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
			// create css and images folder
			mkdir($directory . "/css", 0777);
			mkdir($directory . "/images", 0777);
			
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
			
			// write output to file
			fwrite($fileHandle, $output);
			fclose($fileHandle);
			
			
			/*
			var_dump($images);
			
			var_dump($output);
			
			
			var_dump($linkedCSS);
			var_dump($newLinkedCSS);
			
			
			*/
			
			
			/************************************************************************************
			 * All files are ready now, create a ZIP archive and set headers for downloading
			************************************************************************************/
			// create zip file
			$zipFile = $exportTempFolder . DIRECTORY_SEPARATOR . $mod . "_" . $itemId . ".zip";
			
			if (file_exists(realpath($zipfile))) unlink($zipfile);
			
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
			
			/*

     //find images in string
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

        if(isset($out['amp;iid']))
         {
            $index = $out['amp;iid'];
         }
        elseif(isset($out['iid']))
         {
            $index = $out['iid'];
         }
        if(isset($index))
         {
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
     }

     preg_match_all('~\<img\s{1}style=" padding:5px;"\s{1}src=\"(.*)\"\s{1}a~u', $output, $imgatt_array);
     $i = 0;
     foreach($imgatt_array[1] as $img)
     {
       $img = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       $img = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       #$img = str_replace($c_single_entry_point.'/','',$img);
       #$img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       #$img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       $imgatt_array[1][$i] = str_replace('_thumb.png','',$img);
       foreach($filearray as $fi)
       {
          $imgname = strstr($fi,$imgatt_array[1][$i]);
          $img = preg_replace('~cid\d{1,}_\d{1,}_~u','',$img);

           if($imgname != false)
         {
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
     }

     // thumbs_new
     preg_match_all('~\<img(.*)src=\"((.*)_thumb.png)\"~u', $output, $imgatt_array);
     foreach($imgatt_array[2] as $img)
     {
       $img_old = $img;
       $img = str_replace($c_single_entry_point.'/','',$img);
       $img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       $img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       $img = mb_substr($img,0,mb_strlen($img)/2);
       $img = preg_replace('~cid\d{1,}_\d{1,}_~u','',$img);
       $output = str_replace($img_old,$img,$output);
     }

     $output = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$output);
     $output = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$output);
     $output = preg_replace('~cid\d{1,}_\d{1,}_~u','',$output);

     

     //copy CSS File
     if (isset($params['view_mode'])){
        $csssrc = 'htdocs/commsy_pda_css.php';
     } else {
        $csssrc = 'htdocs/commsy_print_css.php';
     }
     $csstarget = $directory.'/stylesheet.css';

     mkdir($directory.'/css', 0777);

     if (isset($params['view_mode'])){
        $url_to_style = $c_commsy_domain.$c_commsy_url_path.'/css/commsy_pda_css.php?cid='.$environment->getCurrentContextID();
     } else {
        $url_to_style = $c_commsy_domain.$c_commsy_url_path.'/css/commsy_print_css.php?cid='.$environment->getCurrentContextID();
     }
     getCSS($directory.'/css/stylesheet.css',$url_to_style);
     unset($url_to_style);

     $url_to_style = $c_commsy_domain.$c_commsy_url_path.'/css/commsy_myarea_css.php?cid='.$environment->getCurrentContextID();
     getCSS($directory.'/css/stylesheet2.css',$url_to_style);
     unset($url_to_style);
			 */
		}
	}
?>
