<?php
	require_once('classes/controller/cs_ajax_controller.php');
	require_once("libs/jsonrpcphp/jsonRPCClient.php");

	class cs_ajax_limesurvey_controller extends cs_ajax_controller
	{
		private $client = null;
		private $sessionKey = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		private function initClient()
		{
			// get the current portal item
			$currentPortalItem = $this->_environment->getCurrentPortalItem();
			
			// try to connect
			try
			{
				$this->client = new jsonRPCClient($currentPortalItem->getLimeSurveyJsonRpcUrl());
				
				/*
				 * On success:	A session key (string)
				 * On failure:	For protocol-level errors (invalid format etc), an error message. For invalid username and password,
				 * 				returns a null error and the result body contains a 'status' name-value pair with the error message.
				 */
				$this->sessionKey = $this->client->get_session_key($currentPortalItem->getLimeSurveyAdminUser(), $currentPortalItem->getLimeSurveyAdminPassword());
				
				if ( is_array($this->sessionKey) && isset($this->sessionKey['status']) )
				{
					$this->setErrorReturn("020", $this->sessionKey['status']);
					echo $this->_return;
					exit;
				}
			}
			catch ( Exception $e )
			{
				$this->setErrorReturn("020", "connection problems");
				echo $this->_return;
				exit;
			}
		}
		
		private function closeClient()
		{
			if ( !(is_array($this->sessionKey) && isset($this->sessionKey['status'])) )
			{
				$this->client->release_session_key($this->sessionKey);
			}
		}
		
		public function actionGetTemplates()
		{
			$return = array(
				"surveys"	=> array()
			);
			
			$this->initClient();
			
			$surveyList = $this->client->list_surveys($this->sessionKey);
			foreach ( $surveyList as $survey )
			{
				$surveyProperties = $this->client->get_survey_properties($this->sessionKey, $survey['sid'], array("listpublic"));
				
				// if there was an error
				if ( !isset($surveyProperties["listpublic"]) )
				{
					$this->closeClient();
					$this->setErrorReturn("903", $surveyProperties["status"]);
					echo $this->_return;
					exit;
				}
				
				if ( $surveyProperties["listpublic"] === "Y" )
				{
					$return["surveys"][] = $survey;
				}
			}
			
			$this->closeClient();

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionCreateSurvey()
		{
			$templateId = $this->_data["templateId"];
			
			$return = array(
				"newSurveyId"	=> null
			);
			
			$this->initClient();
			
			// export the template survey as lss
			$templateLSSBase64 = $this->client->export_survey($this->sessionKey, $templateId);
			$return["test"] = $templateLSSBase64;
			
			// reimport
			$newSurveyId = $this->client->import_survey($this->sessionKey, $templateLSSBase64, "lss");
			$return["newSurveyId"] = $newSurveyId;
			
			if ( !is_integer($newSurveyId) )
			{
				$this->closeClient();
				$this->setErrorReturn("903", $newSurveyId["status"]);
				echo $this->_return;
				exit;
			}
			
			// set public listing to false
			$newProperties = $this->client->set_survey_properties($this->sessionKey, $newSurveyId, array("listpublic" => "N"));
			if ( !isset($newProperties["listpublic"]) || $newProperties["listpublic"] !== true )
			{
				$this->closeClient();
				$this->setErrorReturn("903", $newProperties["status"]);
				echo $this->_return;
				exit;
			}
			
			// store this survey in the list of the current room
			$currentContextItem = $this->_environment->getCurrentContextItem();
			$surveyIDs = $currentContextItem->getLimeSurveySurveyIDs();
			$surveyIDs[] = $newSurveyId;
			$currentContextItem->setLimeSurveySurveyIDs($surveyIDs);
			$currentContextItem->save();
			
			$this->closeClient();
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionQuery()
		{
			$return = array(
				"items"	=> array(),
				"total"	=> null
			);
			
			// get all survey ids for the current room
			$currentContextItem = $this->_environment->getCurrentContextItem();
			$surveyIDs = $currentContextItem->getLimeSurveySurveyIDs();
			
			// open rpc connection
			$this->initClient();
			
			// collect the survey data
			foreach ( $surveyIDs as $surveyID )
			{
				$surveyProperties = $this->client->get_survey_properties($this->sessionKey, $surveyID, array("active", "datecreated"));
				// if there was an error
				if ( isset($surveyProperties["status"]) )
				{
					$this->closeClient();
					$this->setErrorReturn("903", $surveyProperties["status"]);
					echo $this->_return;
					exit;
				}
				
				
				$return["items"][] = array
				(
					"sid"			=> $surveyID,
					"active"		=> $surveyProperties["active"] === "Y" ? true : false,
					"datecreated"	=> $surveyProperties["datecreated"]
				);
			}
			
			// close
			$this->closeClient();
			
			$return["total"] = sizeof($surveyIDs);
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
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