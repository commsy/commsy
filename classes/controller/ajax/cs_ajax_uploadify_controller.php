<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_uploadify_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actionUpload() {
			// debugging file output
			/*
			$file = fopen("output.txt", "w+");
			error_reporting(E_ALL);
			ob_start();
			if(!isset($_FILES)) fputs($file, "not set\n");
			else fputs($file, "set\n");
			fputs($file, pr($_FILES));
			fputs($file, pr($_REQUEST));
			*/
			
			if(!empty($_FILES)) {
				include_once('functions/development_functions.php');
				
			   $post_file_ids = array();
			   $tempFile = $_FILES['Filedata']['tmp_name'];
			
			   $file_upload_rubric = $_REQUEST['file_upload_rubric'];
			   
			   $session = $this->_environment->getSessionItem();
			   
			   if($session->issetValue($file_upload_rubric . '_add_files')) {
			      $file_array = $session->getValue($file_upload_rubric . '_add_files');
			   } else {
			      $file_array = array();
			   }
			
			   if(!empty($tempFile) && $_FILES['Filedata']['size'] > 0) {
			      $disc_manager = $this->_environment->getDiscManager();
			      if(   isset($_REQUEST['c_virus_scan']) &&
			            $_REQUEST['c_virus_scan'] &&
			            isset($_REQUEST['c_virus_scan_cron']) &&
			            !empty($_REQUEST['c_virus_scan_cron']) &&
			            !$_REQUEST['c_virus_scan_cron']) {
			         // use virus scanner
			         require_once('classes/cs_virus_scan.php');
			         $virus_scanner = new cs_virus_scan($this->_environment);
			         if ( $virus_scanner->isClean($tempFile,$_FILES['Filedata']['name']) ) {
			            $temp_array = array();
			            $temp_array['name'] = $_FILES['Filedata']['name'];
			            $temp_array['tmp_name'] = $disc_manager->moveUploadedFileToTempFolder($tempFile);
			            $temp_array['file_id'] = $temp_array['name'].'_' . getCurrentDateTimeInMySQL();
			            $file_array[] = $temp_array;
			         }
			      } else {
			         // do not use virus scanner
			         require_once('functions/date_functions.php');
			         $temp_array = array();
			         $temp_array['name'] = $_FILES['Filedata']['name'];
			         $temp_array['tmp_name'] = $disc_manager->moveUploadedFileToTempFolder($tempFile);
			         $temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
			         $file_array[] = $temp_array;
			      }
			      unset($disc_manager);
			   }
			   if(count($file_array) > 0) {
			      $session->setValue($file_upload_rubric . '_add_files', $file_array);
			   } else {
			      $session->unsetValue($file_upload_rubric . '_add_files');
			   }
			
			   echo $temp_array['file_id'];
			}
			
			$this->_environment->getSessionManager()->save($session);
			
			/*
			fputs($file, ob_get_clean());
			fclose($file);
			*/
			
			/*
			$return = array();
			
			echo json_encode($return);
			*/
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}