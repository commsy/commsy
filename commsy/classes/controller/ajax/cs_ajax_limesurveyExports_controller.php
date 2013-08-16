<?php
	require_once('classes/controller/cs_ajax_controller.php');
	require_once("libs/jsonrpcphp/jsonRPCClient.php");

	class cs_ajax_limesurveyExports_controller extends cs_ajax_controller
	{
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionDelete()
		{
			$surveyId = $this->_data["surveyId"];
			$timestamp = $this->_data["timestamp"];
			
			// get the export folder for this single export
			$discManager = $this->_environment->getDiscManager();
			$mainExportFolder = $discManager->getFilePath() . "limesurvey_export/";
			
			$surveyFolder = $mainExportFolder . $surveyId . "/";
			$timestampFolder = $surveyFolder . $timestamp . "/";
			$success = false;
			
			if ( is_dir($timestampFolder) )
			{
				if ( $discManager->removeDirectory($timestampFolder) )
				{
					$success = true;
				}
				
				// if the survey folder is empty after deleting the timestamp folder, we can delete it too
				$surveyFolderContent = $this->getDirectoryArray($surveyFolder);
				if ( empty($surveyFolderContent) )
				{
					$discManager->removeDirectory($surveyFolder);
				}
			}
			
			if ( $success )
			{
				$this->setSuccessfullDataReturn($return);
				echo $this->_return;
			}
			else
			{
				$this->setErrorReturn("904", "could not delete limesurvey export", array());
				echo $this->_return;
				exit;
			}
		}
		
		public function actionQuery()
		{
			$return = array(
				"items"	=> array(),
				"total"	=> 0
			);
			
			// get the limesurvey export folder
			$discManager = $this->_environment->getDiscManager();
			$filePath = $discManager->getFilePath() . "limesurvey_export/";
			
			// scan the limesurvey_export directory in the room folder
			$surveyDirectoryArray = $this->getDirectoryArray($filePath);
			foreach ( $surveyDirectoryArray as $surveyDirectory )
			{
				// get the survey id from the path
				$surveyDirectoryExplode = explode("/", $surveyDirectory);
				$secondLastIndex = sizeof($surveyDirectoryExplode) - 2;
				if ( isset($surveyDirectoryExplode[$secondLastIndex]) )
				{
					$surveyId = $surveyDirectoryExplode[$secondLastIndex];
					
					// scan the timestamp folder in the survey folder
					$timestampDirectoryArray = $this->getDirectoryArray($surveyDirectory);
					foreach ( $timestampDirectoryArray as $timestampDirectory )
					{
						// extract the timestamp from the path
						$timestampDirectoryExplode = explode("/", $timestampDirectory);
							
						$secondLastIndex = sizeof($timestampDirectoryExplode) - 2;
						if ( isset($timestampDirectoryExplode[$secondLastIndex]) )
						{
							$timestamp = $timestampDirectoryExplode[$secondLastIndex];
							
							// look for exported files
							$fileSurvey = "";
							$fileStatistics = "";
							$fileResponses = "";
							
							if ( is_file($timestampDirectory . "survey.lss") )
							{
								$fileSurvey = $timestampDirectory . "survey.lss";
							}
							if ( is_file($timestampDirectory . "statistics.pdf") )
							{
								$fileStatistics = $timestampDirectory . "statistics.pdf";
							}
							if ( is_file($timestampDirectory . "responses.csv") )
							{
								$fileResponses = $timestampDirectory . "responses.csv";
							}
							
							// get the survey title by parsing the survey.lss file
							$title = "";
							if ( !empty($fileSurvey) )
							{
								$lssFileContent = file_get_contents($fileSurvey);
								if ( $lssFileContent )
								{
									if ( class_exists("SimpleXMLElement") )
									{
										$lssXml = new SimpleXMLElement($lssFileContent, LIBXML_NOCDATA);
											
										$result = $lssXml->xpath('/document/surveys_languagesettings/rows/row/surveyls_title');
										$simpleXMLElement = $result[0];
										if ( $simpleXMLElement )
										{
											$elementArray = (array) $simpleXMLElement;
											if ( isset($elementArray[0]) )
											{
												$title = $elementArray[0];
											}
										}
									}
								}
							}
								
							// add item
							$return["items"][] = array
							(
								"surveyId"		=> $surveyId,
								"timestamp"		=> $timestamp,
								"title"			=> $title,
								"exportDate"	=> getDateTimeInLang(date("Y-m-d H:i:s", $timestamp)),
								"files"			=> array
								(
									"survey"		=> $fileSurvey,
									"statistics"	=> $fileStatistics,
									"responses"		=> $fileResponses
								)
							);
								
							$return["total"]++;
						}
					}
				}
			}
				
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		private function getDirectoryArray($path)
		{
			$return = array();
			
			if ( ($directoryArray = scandir($path)) !== false )
			{
				foreach ( $directoryArray as $directoryContent )
				{
					if ( $directoryContent === "." || $directoryContent === ".." )
					{
						continue;
					}
						
					$contentPath = $path . $directoryContent;
					if ( !is_dir($contentPath) )
					{
						continue;
					}
						
					// directory found
					$return[] = $contentPath . "/";
				}
			}
			
			return $return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process()
		{
			$currentPortalItem = $this->_environment->getCurrentPortalItem();
			$currentContextItem = $this->_environment->getCurrentContextItem();
			
			if (	!($this->_environment->inPortal() || $this->_environment->inServer()) &&
					$currentPortalItem->withLimeSurveyFunctions() &&
					$currentPortalItem->isLimeSurveyActive() &&
					$currentContextItem->isLimeSurveyActive() )
			{
				// call parent
				parent::process();
			}
		}
	}