<?php
	require_once('classes/controller/cs_room_controller.php');

	class cs_agb_controller extends cs_room_controller {

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'agb';
			
			$this->_addition_selects = false;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}

		public function actionIndex() {
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
		    $portal_user = $this->_environment->getPortalUserItem();
			// portal AGB - redirected in commy.php to portal context
			/*
			 * $current_context = $environment->getCurrentContextItem();
			if (!$current_context->isPortal() && !$current_context->isServer()) {
				
				$portal_user = $current_user->getRelatedCommSyUserItem();
				if ( isset($portal_user) and $portal_user->isUser() and !$portal_user->isRoot() ) {
					$current_portal = $environment->getCurrentPortalItem();
					$user_agb_date = $portal_user->getAGBAcceptanceDate();
					$portal_agb_date = $current_portal->getAGBChangeDate();
					
					if ( $user_agb_date < $portal_agb_date && $current_portal->getAGBStatus() == 1 ) {
						redirect($current_portal->getItemID(), "agb", "index");
					}
				}
			}
			 */
			
			// check for agb
			$showAGB = false;
			if ( ($current_user->isUser() or $portal_user->isUser()) && !$current_user->isRoot()) {
				$user_agb_date = $current_user->getAGBAcceptanceDate();
				$context_agb_date = $current_context->getAGBChangeDate();
				if ( $user_agb_date < $context_agb_date && $current_context->getAGBStatus() == 1 ) {
					$showAGB = true;
				} else if($portal_user->isUser() && $current_context->getAGBStatus() == 1){
					$showAGB = true;
				}
			}
			if ($showAGB) {
				if (empty($_POST)) {
					$currentContext = $this->_environment->getCurrentContextItem();
					$textArray = $currentContext->getAGBTextArray();
					
					$language = $currentContext->getLanguage();
					if ($language == "user") {
						$language = getSelectedLanguage();
					}
					
					if (!empty($textArray[mb_strtoupper($language, 'UTF-8')])) {
						 //$text = $this->_environment->getTextConverter()->cleanDataFromTextArea($textArray[mb_strtoupper($language, 'UTF-8')]);
						 
						 $text = $textArray[mb_strtoupper($language, 'UTF-8')];
					} else {
						foreach($textArray as $key => $value) {
							if (!empty($value)) {
								//$text = $this->_environment->getTextConverter()->cleanDataFromTextArea($textArray[mb_strtoupper($key, 'UTF-8')]);
								$text = $textArray[mb_strtoupper($key, 'UTF-8')];
					         $translator = $this->_environment->getTranslationObject();
								$text .= '<br/><br/><b>'.$translator->getMessage('AGB_NO_AGS_FOUND_IN_SELECTED_LANGUAGE').'</b>';
							}
						}
					}
					
					$this->assign("agb", "text", $text);
				} else {
					$postSubmitArray = array_values($_POST["submit"]);					
					$command = $postSubmitArray[0];
					
					$translator = $this->_environment->getTranslationObject();
					$environment = $this->_environment;
					
					if ( isOption($command, $translator->getMessage('AGB_ACCEPTANCE_BUTTON')) ) {
						$current_user = $environment->getCurrentUserItem();
						$current_user->setAGBAcceptance();
						$current_user->save();
						$session_item = $environment->getSessionItem();
						$history = $session_item->getValue('history');
						/*
						if ( !empty($history[0]['context'])
								and $history[0]['module'] != 'agb'
						) {
							$params = $history[0]['parameter'];
							unset($params['cs_modus']);
							redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params);
						} elseif ( !empty($history[1]['context']) ) {
							$params = $history[1]['parameter'];
							unset($params['cs_modus']);
							redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$params);
						} else {
							redirect($environment->getCurrentContextID(),'home','index',array());
						}*/
						redirect($environment->getCurrentContextID(),'home','index',array());
						exit();
					} elseif ( isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON'))
							or isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_ROOM'))
							or isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_PORTAL'))
							or isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))
					) {
						$session_item = $environment->getSessionItem();
						$history = $session_item->getValue('history');
					
						//  zur Seite leiten
						if ( !empty($history[0]['context'])
								and $history[0]['module'] != 'agb'
								and !isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))
						) {
							// Raum betreten
							$params = $history[0]['parameter'];
							unset($params['cs_modus']);
							redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params);
						} elseif ( !empty($history[1]['context']) and !isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
							// zurück in vorigen Raum
							$params = $history[1]['parameter'];
							unset($params['cs_modus']);
							if ( $history[1]['context'] == $environment->getCurrentContextID()
									and isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))
							) {
								redirect($environment->getCurrentPortalID(),'home','index',array('room_id' => $environment->getCurrentContextID()));
							} else {
								redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$params);
							}
						} else {
							$session_manager = $environment->getSessionManager();
							include_once('pages/context_logout.php');
						}
						exit();
					}
				}
				
				
				/*
				$this->setHeadline($this->_translator->getMessage('AGB_CHANGE_TITLE'));
				$this->_form->addText('agb_text','',$this->_agb_text);
				if ( !($this->_environment->getCurrentModule() == 'agb' and
						$this->_environment->getCurrentFunction() == 'index')
				) {
					$this->_form->addEmptyLine();
					if ( !$this->_environment->inPortal() ) {
						$this->_form->addButtonBar('option',
								$this->_translator->getMessage('AGB_ACCEPTANCE_BUTTON'),
								$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),
								$this->_translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_ROOM'));
					} else {
						if($this->_environment->getCurrentUserItem()->getCreationDate() > getCurrentDateTimeMinusMinutesInMySQL(1) ) {
							$this->_form->addHidden('is_no_user', '1');
							$this->_form->addButtonBar('option',
									$this->_translator->getMessage('AGB_ACCEPTANCE_BUTTON'),
									$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
				
						}
						else {
							$this->_form->addButtonBar('option',
									$this->_translator->getMessage('AGB_ACCEPTANCE_BUTTON'),
									$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),
									$this->_translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_PORTAL'));
						}
					}
				}*/
				
				
			} else {
				redirect($this->_environment->getCurrentContextID(), "home", "index");
			}
		}
	}