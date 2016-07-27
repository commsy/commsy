<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_upload_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actionUpload() {
			if(!isset($_FILES) || empty($_FILES)) exit;
			
			// debugging file output
			/*
			$file = fopen("output.txt", "w+");
			error_reporting(E_ALL);
			ob_start();
			/*
			if(!isset($_FILES)) fputs($file, "not set\n");
			else fputs($file, "set\n");
			fputs($file, var_dump($_FILES));
			fputs($file, var_dump($_REQUEST));
			fputs($file, "================================");
			*/
			
			// get post data
			$postdata = array();
			$data = "";
			foreach ($_POST as $key => $val) {
				$data .= $key ."=" . $val . ",";
				$postdata[$key] = $val;
			}
			
			if(isset($_FILES["uploadedfile0"])) { // maybe also uploadedfile???
				// from html(single or multi) - but one after another
				
				$count = 0;
				while(isset($_FILES["uploadedfile" . $count])) {
					$info = $this->doUpload($_FILES["uploadedfile" . $count], $postdata["file_upload_rubric"]);
					$postdata = $info;//array_merge($info, $postdata);
					
					$count++;
				}
				
				$json = false;
			}
			
			elseif(isset($_FILES["uploadedfile"])) {
				// from html(single)
				
				$info = $this->doUpload($_FILES["uploadedfile"], $postdata["file_upload_rubric"]);
				$postdata = $info;
				
				$json = false;
			}
			
			elseif(isset($_FILES["uploadedfiles"])) {
				// from html5(array)
				
				$info = $this->doUpload($_FILES["uploadedfiles"], $postdata["file_upload_rubric"]);
				$postdata = $info;//array_merge($info, $postdata);
				
				$json = true;
			}
			
			/*
			 * If IFrame plugin is used, the code on the client side gets tricky, as reading back
			 * from an iframe presents problems. In order to read the iframe return data accurately cross browser,
			 * the code needs to be wrapped in a <textarea>. 
			 */
			
			// html gets a json array back
			$data = json_encode($postdata);
			
			/*
			fputs($file, ob_get_clean());
			fclose($file);
			*/
			
			if($json === true) {
				echo $data;
			} else {
?>
			<textarea><?php echo $data; ?></textarea>
<?php
			}
		}
		
		private function doUpload($uploadData, $file_upload_rubric) {
			$session = $this->_environment->getSessionItem();
			
			$isMulti = is_array($uploadData["name"]);

			$virus_found = false;
			
			$file_array = array();
			if($isMulti) {
				$numFiles = sizeof($uploadData["name"]);
				
				for($i = 0; $i < $numFiles; $i++) {
					$tempFile = $uploadData["tmp_name"][$i];

					global $symfonyContainer;
					$c_virus_scan = $symfonyContainer->getParameter('commsy.clamscan.virus_scan');
					$c_virus_scan_cron = $symfonyContainer->getParameter('commsy.clamscan.virus_scan_cron');

					$c_virus_scan = (!isset($c_virus_scan) || $c_virus_scan === false) ? false : true;
					$c_virus_scan_cron = (!isset($c_virus_scan_cron) || $c_virus_scan_cron === false) ? false : true;
						
					if(!empty($tempFile) && $uploadData["size"][$i] > 0) {
						$disc_manager = $this->_environment->getDiscManager();
				
						if(	isset($c_virus_scan) &&
								$c_virus_scan &&
								isset($c_virus_scan_cron) &&
								//!empty($c_virus_scan_cron) &&
								!$c_virus_scan_cron) {
							// use virus scanner
							require_once('classes/cs_virus_scan.php');
							$virus_scanner = new cs_virus_scan($this->_environment);
							$temp_array = array();

							if ( !$virus_scanner->isClean($tempFile, $uploadData['name']) ) {
								// Error virus detected
								$virus_found = true;
								$temp_array['virus'] = true;
								$temp_array['virusname'] = $virus_scanner->getVirusName();
							} else {
								$temp_array['tmp_name'] = $disc_manager->moveUploadedFileToTempFolder($tempFile);
								$temp_array['file_id'] = $temp_array['name'].'_' . getCurrentDateTimeInMySQL();
							}
							$temp_array['name'] = $uploadData['name'][$i];
							$file_array[] = $temp_array;
						} else {
							// do not use virus scanner
							require_once('functions/date_functions.php');
							$temp_array = array();
							$temp_array['name'] = $uploadData['name'][$i];
							$temp_array['tmp_name'] = $disc_manager->moveUploadedFileToTempFolder($tempFile);
							$temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
							$file_array[] = $temp_array;
						}
						unset($disc_manager);
					}
				}
			} else {
				$tempFile = $uploadData["tmp_name"];

				global $symfonyContainer;
				$c_virus_scan = $symfonyContainer->getParameter('commsy.clamscan.virus_scan');
				$c_virus_scan_cron = $symfonyContainer->getParameter('commsy.clamscan.virus_scan_cron');
					
				if(!empty($tempFile) && $uploadData["size"] > 0) {
					$disc_manager = $this->_environment->getDiscManager();
				
					if(	isset($c_virus_scan) &&
							$c_virus_scan &&
							isset($c_virus_scan_cron) &&
							//!empty($c_virus_scan_cron) &&
							!$c_virus_scan_cron) {
							
						// use virus scanner
						require_once('classes/cs_virus_scan.php');
						$virus_scanner = new cs_virus_scan($this->_environment);

						$temp_array = array();

						if ( !$virus_scanner->isClean($tempFile, $uploadData['name']) ) {
							// Error virus detected
							$virus_found = true;
							$temp_array['virus'] = true;
							$temp_array['virusname'] = $virus_scanner->getVirusName();
						} else {
							$temp_array['tmp_name'] = $disc_manager->moveUploadedFileToTempFolder($tempFile);
							$temp_array['file_id'] = $temp_array['name'].'_' . getCurrentDateTimeInMySQL();
						}
						$temp_array['name'] = $uploadData['name'];
						$file_array[] = $temp_array;
					} else {
						// do not use virus scanner
						require_once('functions/date_functions.php');
						$temp_array = array();
						$temp_array['name'] = $uploadData['name'];
						$temp_array['tmp_name'] = $disc_manager->moveUploadedFileToTempFolder($tempFile);
						$temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
						$file_array[] = $temp_array;
					}
					unset($disc_manager);
				}
			}
			
			$return = array();
			$sessionArray = array();
			
			if(sizeof($file_array) > 1) {
				foreach($file_array as $file) {
					$return[] = array(
						"file"		=> $file["tmp_name"],
						"name"		=> $file["name"],
						"type"		=> "",
						"file_id"	=> $file["file_id"],
						"virus"		=> $file["virus"],
						"virusname" => $file["virusname"]
					);
				}
				
				foreach($return as $file) {
					$sessionArray[$file["file_id"]] = array(
							"tmp_name"	=> $file["file"],
							"name"		=> $file["name"]
					);
				}
			} else {
				$return = array(
					"file"		=> $file_array[0]["tmp_name"],
					"name"		=> $file_array[0]["name"],
					"type"		=> "",
					"file_id"	=> $file_array[0]["file_id"],
					"virus"		=> $file_array[0]["virus"],
					"virusname" => $file_array[0]["virusname"]
				);
				
				$sessionArray[$return["file_id"]] = array(
						"tmp_name"	=> $return["file"],
						"name"		=> $return["name"]
				);
			}
			
			// merge current upload data with last one - session will be cleaned when storing item
			$currentSessionArray = array();
			if($session->issetValue(/*$file_upload_rubric . '_add_files'*/"add_files")) {
				$currentSessionArray = $session->getValue("add_files");
			}
			
			foreach($currentSessionArray as $key => $value) {
				$sessionArray[$key] = $value;
			}
			
			$session->setValue("add_files", $sessionArray);
			$this->_environment->getSessionManager()->save($session);
			
			return $return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}