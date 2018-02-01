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
				global $symfonyContainer;
				$c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
				$c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');
				
				if ( isset($c_proxy_ip) && isset($c_proxy_port) && !empty($c_proxy_ip) && !empty($c_proxy_port) )
				{
					$this->client = new jsonRPCClient($currentPortalItem->getLimeSurveyJsonRpcUrl(), false, $c_proxy_ip . ":" . $c_proxy_port);
				}
				else
				{
					$this->client = new jsonRPCClient($currentPortalItem->getLimeSurveyJsonRpcUrl());
				}
				
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
			if ( !(isset($surveyList["status"]) && $surveyList["status"] === "No surveys found") )
			{
				foreach ( $surveyList as $survey )
				{
					// templates are identified by the "4CS:" prefix in survey name
					if ( mb_substr($survey["surveyls_title"], 0, 4) === "4CS:" )
					{
						$survey["surveyls_title"] = mb_substr($survey["surveyls_title"], 4);
						$return["surveys"][] = $survey;
					}
				}
			}
			
			$this->closeClient();

			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionGetGroups()
		{
			$return = array(
				"groups"	=> array()
			);
			
			$currentContextItem = $this->_environment->getCurrentContextItem();
			
			$groupManager = $this->_environment->getGroupManager();
			$groupManager->reset();
			$groupManager->setContextLimit($this->_environment->getCurrentContextID());
			$groupManager->setTypeLimit("group");
			
			$groupManager->select();
			$groupList = $groupManager->get();
			
			$group = $groupList->getFirst();
			while ( $group )
			{
				
				$members = array();
				$memberList = $group->getMemberItemList();
				
				$member = $memberList->getFirst();
				while( $member )
				{
					$members[] = array(
						"email"		=> $member->getEmail(),
						"forname"	=> $member->getFirstName(),
						"surname"	=> $member->getLastName()
					);
					
					$member = $memberList->getNext();
				}
				
				$return["groups"][] = array
				(
					"id"			=> $group->getItemID(),
					"title"			=> $group->getTitle(),
					"memberList"	=> $members
				);
				
				$group = $groupList->getNext();
			}
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionCreateSurvey()
		{
			$templateId = $this->_data["templateId"];
			$newSurveyTitle = $this->_data["surveyTitle"];
			$surveyExpires = $this->_data["surveyExpires"];
			
			$return = array(
				"newSurveyId"	=> null
			);
			
			$this->initClient();
			
			$currentContextItem = $this->_environment->getCurrentContextItem();
			
			// export the template survey as lss
			$templateLSSBase64 = $this->client->export_survey($this->sessionKey, $templateId);
			
			// get a new title
			$newSurveyTitle = trim($newSurveyTitle);
			if ( empty($newSurveyTitle) )
			{
				// get the title from the survey template
				$survey = $this->getSurveyFromLimeSurvey($this->sessionKey, $templateId);
				
				// templates are identified by the "4CS:" prefix in survey name
				if ( mb_substr($survey["surveyls_title"], 0, 4) === "4CS:" )
				{
					$newSurveyTitle = mb_substr($survey["surveyls_title"], 4);
				}
				else
				{
					$newSurveyTitle = $survey["surveyls_title"];
				}
			}
			else
			{
				// sanitize the input
				$textConverter = $this->_environment->getTextConverter();
				$newSurveyTitle = $textConverter->sanitizeHTML($newSurveyTitle);
			}
			
			// append room name
			$newSurveyTitle .= " - " . $currentContextItem->getTitle();
			
			// reimport
			$newSurveyId = $this->client->import_survey($this->sessionKey, $templateLSSBase64, "lss", $newSurveyTitle);
			$return["newSurveyId"] = $newSurveyId;
			
			if ( !is_integer($newSurveyId) )
			{
				$this->closeClient();
				$this->setErrorReturn("903", $newSurveyId["status"]);
				echo $this->_return;
				exit;
			}
			
			// expires
			if ( isset($surveyExpires) && !empty($surveyExpires) )
			{
				$propertiesResult = $this->client->set_survey_properties($this->sessionKey, $newSurveyId, array("expires" => $surveyExpires));
				if ( !isset($propertiesResult["expires"]) || $propertiesResult["expires"] !== true )
				{
					// something went wrong, delete the survey
					$this->client->delete_survey($this->sessionKey, $newSurveyId);
					
					$this->closeClient();
					$this->setErrorReturn("903", $propertiesResult["status"]);
					echo $this->_return;
					exit;
				}
			}
			
			// store this survey in the list of the current room
			$surveyIDs = $currentContextItem->getLimeSurveySurveyIDs();
			$surveyIDs[] = $newSurveyId;
			$currentContextItem->setLimeSurveySurveyIDs($surveyIDs);
			$currentContextItem->save();
			
			$this->closeClient();
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionDelete()
		{
			$surveyId = $this->_data["surveyId"];
			
			$this->initClient();
			
			// delete the survey
			$deleteStatus = $this->client->delete_survey($this->sessionKey, $surveyId);
			
			if ( isset($deleteStatus["status"]) && $deleteStatus["status"] !== "OK" )
			{
				$this->setErrorReturn("904", $deleteStatus["status"]);
				echo $this->_return;
				exit;
			}
			
			$this->closeClient();
			
			// delete from room extras
			$currentContextItem = $this->_environment->getCurrentContextItem();
			$surveyIDs = $currentContextItem->getLimeSurveySurveyIDs();
			array_splice($surveyIDs, array_search($surveyId, $surveyIDs), 1);
			$currentContextItem->setLimeSurveySurveyIDs($surveyIDs);
			$currentContextItem->save();
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionActivateSurvey()
		{
			$surveyId = $this->_data["surveyId"];
			
			$this->initclient();
			
			// set new active state
			$activateSurvey = $this->client->activate_survey($this->sessionKey, $surveyId);
			if ( isset($activateSurvey["status"]) && $activateSurvey["status"] !== "OK" )
			{
				$this->closeClient();
				$this->setErrorReturn("903", $activateSurvey["status"]);
				echo $this->_return;
				exit;
			}
			
			$this->closeClient();
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionGetDisplayedSurveys()
		{
			$currentContextItem = $this->_environment->getCurrentContextItem();
			$currentPortalItem = $this->_environment->getCurrentPortalItem();
			$currentUserItem = $this->_environment->getCurrentUserItem();
			$currentUserMail = $currentUserItem->getEmail();
			
			$surveyIDs = $currentContextItem->getLimeSurveySurveyIDs();
			$rpcPathParsed = parse_url($currentPortalItem->getLimeSurveyJsonRpcUrl());
			$surveyBaseUrl = $rpcPathParsed['scheme'] . "://" . $rpcPathParsed['host'] . "/index.php/";
			
			$return = array(
				"surveys"	=> array()
			);
			
			$this->initClient();
			
			$surveyList = $this->client->list_surveys($this->sessionKey);
			if ( !(isset($surveyList["status"]) && $surveyList["status"] === "No surveys found") )
			{
				foreach ( $surveyList as $survey )
				{
					if ( $survey["active"] === "Y" && in_array($survey["sid"], $surveyIDs) )
					{
						$surveyUrl = $surveyBaseUrl;
						
						// check if this is a survey with tokens
						$participantList = $this->client->list_participants($this->sessionKey, $survey['sid']);
						if ( !isset($participantList['status']) || $participantList['status'] !== "Error: No token table" )
						{
							foreach ( $participantList as $participant )
							{
								// compare the participant mail with the one of the current user
								if ( isset($participant['participant_info']['email']) && $participant['participant_info']['email'] == $currentUserMail )
								{
									$surveyUrl .= "survey/index/sid/" . $survey['sid'] . "/token/" . $participant['token'];
									break;
								}
							}
						}
						else
						{
							$surveyUrl .= $survey['sid'];
						}
						
						$return["surveys"][] = array
						(
							'title'			=> $survey['surveyls_title'],
							'url'			=> $surveyUrl
						);
					}
				}
			}
			
			$this->closeClient();
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionQuery()
		{
			$return = array(
				"items"	=> array(),
				"total"	=> 0
			);
			
			// get all survey ids for the current room
			$currentContextItem = $this->_environment->getCurrentContextItem();
			$surveyIDs = $currentContextItem->getLimeSurveySurveyIDs();
			$markedForDeletionIDs = array();
			
			if ( !empty($surveyIDs) )
			{
				// open rpc connection
				$this->initClient();
				
				// get the LimeSurvey survey list
				$surveyList = $this->client->list_surveys($this->sessionKey);
				
				if ( !(isset($surveyList["status"]) && $surveyList["status"] === "No surveys found") )
				{
					// collect the survey data
					foreach ( $surveyIDs as $surveyID )
					{
						$survey = $this->getSurveyFromLimeSurvey($this->sessionKey, $surveyID);
						
						if ( $survey !== null )
						{
							$return["items"][] = array
							(
								"sid"			=> $surveyID,
								"active"		=> ($survey["active"] === "Y") ? true : false,
								"expires"		=> $survey["expires"] ? getDateInLang($survey["expires"]) : "-",
								"title"			=> $survey["surveyls_title"]
							);
						}
						else
						{
							// If the survey was not in the list, then it has been deleted in LimeSurvey, but not in CommSy.
							// Store the id, to remove the survey from the room survey list
							$markedForDeletionIDs[] = $surveyID;
						}
					}
				}
					
				// close
				$this->closeClient();
			}
			
			if ( !empty($markedForDeletionIDs) )
			{
				$newSurveyIDs = array_diff($surveyIDs, $markedForDeletionIDs);
				
				$currentContextItem->setLimeSurveySurveyIDs($newSurveyIDs);
				$currentContextItem->save();
			}
			
			$return["total"] = sizeof($surveyIDs);
			
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionExport()
		{
			$surveyId = $this->_data["surveyId"];
			
			// LimeSurvey RPC
			$this->initClient();
			
			/*
			 * collect export data
			 */
			// survey as lss
			$surveyLSSBase64 = $this->client->export_survey($this->sessionKey, $surveyId);
			if ( isset($surveyLSSBase64) && is_array($surveyLSSBase64) )
			{
				$this->closeClient();
				$this->setErrorReturn("903", $surveyLSSBase64["status"]);
				echo $this->_return;
				exit;
			}
			
			// export statistics as pdf
			$surveyStatisticsPDFBase64 = $this->client->export_statistics($this->sessionKey, $surveyId, "pdf", null, "1");
			if ( isset($surveyStatisticsPDFBase64) && is_array($surveyStatisticsPDFBase64) )
			{
				$this->closeClient();
				$this->setErrorReturn("903", $surveyLSSBase64["status"]);
				echo $this->_return;
				exit;
			}
			
			// export responses as csv
			$surveyResponsesCSVBase64 = $this->client->export_responses($this->sessionKey, $surveyId, "csv", null, "all");
			if ( isset($surveyResponsesCSVBase64) && is_array($surveyResponsesCSVBase64) )
			{
				if ( isset($surveyResponsesCSVBase64["status"]) && $surveyResponsesCSVBase64["status"] === "No Data" )
				{
					$surveyResponsesCSVBase64 = "";
				}
				else
				{
					$this->closeClient();
					$this->setErrorReturn("903", $surveyLSSBase64["status"]);
					echo $this->_return;
					exit;
				}
			}
			
			/*
			 * write data to disk
			 */
			// create folder if not present
			$discManager = $this->_environment->getDiscManager();
			$filePath = $discManager->getFilePath() . "limesurvey_export/" . $surveyId . "/" . time() . "/";
			
			if ( !is_dir($filePath) )
			{
				mkdir($filePath, 0777, true);
			}
			
			// write files
			file_put_contents($filePath . "survey.lss", base64_decode($surveyLSSBase64));
			file_put_contents($filePath . "statistics.pdf", base64_decode($surveyStatisticsPDFBase64));
			
			if ( !empty($surveyResponsesCSVBase64) )
			{
				file_put_contents($filePath . "responses.csv", base64_decode($surveyResponsesCSVBase64));
			}
			
			// close
			$this->closeClient();
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionInviteParticipants()
		{
			$groupId = $this->_data["groupId"];
			$mailAddresses = $this->_data["participantMails"];
			$mailSubject = $this->_data["participantMailSubject"];
			$mailText = $this->_data["participantMailtext"];
			$withTokens = $this->_data["withTokens"];
			$surveyId = $this->_data["surveyId"];
			
			$currentContextItem = $this->_environment->getCurrentContextItem();
			
			// LimeSurvey RPC
			$this->initClient();
			
			if ( $withTokens === true )
			{
				$members = array();
				
				// get all member of the selected group - if set
				if ( $groupId !== "none" )
				{
					$groupManager = $this->_environment->getGroupManager();
					$groupItem = $groupManager->getItem($groupId);
					if ( $groupItem !== null )
					{
						$memberList = $groupItem->getMemberItemList();
							
						$member = $memberList->getFirst();
						while( $member )
						{
							$members[] = array(
								"email"		=> $member->getEmail(),
								"firstname"	=> $member->getFirstName(),
								"lastname"	=> $member->getLastName()
							);
					
							$member = $memberList->getNext();
						}
					}
				}
				
				// add additional participants, if given
				$additionalParticipants = array();
				foreach ( $this->_data['formValues'] as $formKey => $formValue )
				{
					if ( !empty($formValue) && preg_match('/^additional(FirstName|LastName|Mail)_(\d+)/', $formKey, $matches) === 1 )
					{
						$key = '';
						if ( $matches[1] === "FirstName" )
						{
							$key = 'firstname';
						}
						else if ( $matches[1] === "LastName" )
						{
							$key = 'lastname';
						}
						else
						{
							$key = 'mail';
						}
						
						$additionalParticipants[$matches[2]][$key] = $formValue;
					}
				}
				
				if ( !empty($additionalParticipants) )
				{
					$members = array_merge($members, $additionalParticipants);
				}
				
				
				// activate tokens
				// unfortunately survey properties does not reflect the correct usetokens setting
				$tokenStatus = $this->client->activate_tokens($this->sessionKey, $surveyId);
				
				// add participants
				$participantData = $this->client->add_participants($this->sessionKey, $surveyId, $members, true);
				
				// check for errors
				if ( isset($participantData["status"]) )
				{
					$this->closeClient();
					$this->setErrorReturn("903", $participantData["status"]);
					echo $this->_return;
					exit;
				}
				
				// remind participants
				$inviteData = $this->client->invite_participants($this->sessionKey, $surveyId);
			}
			else
			{
				// sanitize
				$textConverter = $this->_environment->getTextConverter();
				$mailAddresses = $textConverter->sanitizeHTML($mailAddresses);
				$mailSubject = $textConverter->sanitizeHTML($mailSubject);
				$mailText = $textConverter->sanitizeFullHTML($mailText);
				
				$survey = $this->getSurveyFromLimeSurvey($this->sessionKey, $surveyId);
				$surveyTitle = $survey["surveyls_title"];
				
				$portalItem = $this->_environment->getCurrentPortalItem();
				$rpcPathParsed = parse_url($portalItem->getLimeSurveyJsonRpcUrl());
				$surveyUrl = $rpcPathParsed['scheme'] . "://" . $rpcPathParsed['host'] . "/index.php/" . $surveyId;
				
				// extract the reciever
				$mailArray = array_unique(array_merge(	explode(" ", $mailAddresses),
														explode("\n", $mailAddresses),
														explode(";", $mailAddresses),
														explode(",", $mailAddresses)));
				
				// replace placeholders in mail text
				$mailText = str_replace("{SURVEY_TITLE}", $surveyTitle, $mailText);
				$mailText = str_replace("{SURVEY_URL}", $surveyUrl, $mailText);
				
				$currentUserItem = $this->_environment->getCurrentUserItem();
				
				// send mails
				include_once("classes/cs_mail.php");
				$mail = new cs_mail();

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

                $mail->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
				$mail->set_reply_to_email($currentUserItem->getEmail());
				$mail->set_reply_to_name($currentUserItem->getFullname());
				
				$mail->set_subject($mailSubject);
				$mail->set_message($mailText);
				
				foreach ( $mailArray as $mailAddress )
				{
					$mail->set_to($mailAddress);
					
					$mail->send();
				}
			}
			
			// close
			$this->closeClient();
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		private function getSurveyFromLimeSurvey($sessionKey, $surveyId)
		{
			if ( isset($sessionKey) )
			{
				$surveyList = $this->client->list_surveys($sessionKey);
				if ( !(isset($surveyList["status"]) && $surveyList["status"] === "No surveys found") )
				{
					foreach ( $surveyList as $survey )
					{
						if ( $survey["sid"] == $surveyId )
						{
							return $survey;
						}
					}
				}
			}
			
			return null;
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