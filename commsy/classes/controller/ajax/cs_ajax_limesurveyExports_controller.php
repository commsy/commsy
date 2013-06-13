<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_limesurveyExports_controller extends cs_ajax_controller
	{
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
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
			
			$surveyDirectoryArray = $this->getDirectoryArray($filePath);
			foreach ( $surveyDirectoryArray as $surveyDirectory )
			{
				$timestampDirectoryArray = $this->getDirectoryArray($surveyDirectory);
				foreach ( $timestampDirectoryArray as $timestampDirectory )
				{
					$timestampDirectoryExplode = explode("/", $timestampDirectory);
					
					$secondLastIndex = sizeof($timestampDirectoryExplode) - 2;
					if ( isset($timestampDirectoryExplode[$secondLastIndex]) )			
					{
						$timestamp = $timestampDirectoryExplode[$secondLastIndex];
						
						// add item
						$return["items"][] = array
						(
							"timestamp"		=> $timestamp
						);
						
						$return["total"]++;
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