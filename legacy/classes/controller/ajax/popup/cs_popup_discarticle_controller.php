<?php
	require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

	class cs_popup_discarticle_controller implements cs_rubric_popup_controller {
		private $_environment = null;
		private $_popup_controller = null;

		/**
		* constructor
		*/
		public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
			$this->_environment = $environment;
			$this->_popup_controller = $popup_controller;
		}

		public function initPopup($item, $data) {
			// assign template vars
			$this->assignTemplateVars();

			if($item !== null) {
				// edit

				// files
				$attachment_infos = array();

				$converter = $this->_environment->getTextConverter();
				$file_list = $item->getFileList();

				$file = $file_list->getFirst();
				while($file) {
					#$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
					$info['file_name']	= $converter->filenameFormatting($file->getDisplayName());
					$info['file_icon']	= $file->getFileIcon();
					$info['file_id']	= $file->getFileID();

					$attachment_infos[] = $info;
					$file = $file_list->getNext();
				}
				$this->_popup_controller->assign('item', 'files', $attachment_infos);

				// TODO: check rights
				$this->_popup_controller->assign('item', 'title', $item->getTitle());
				
				$this->_popup_controller->assign('item', 'discarticle_description', $item->getDescription());
			}
		}

		public function save($form_data, $additional = array()) {
			
			if ($additional["contextId"]) {
				$itemManager = $this->_environment->getItemManager();
				$type = $itemManager->getItemType($additional["contextId"]);
			
				$manager = $this->_environment->getManager($type);
				$current_context = $manager->getItem($additional["contextId"]);
			
				if ($type === CS_PRIVATEROOM_TYPE) {
					$this->_environment->changeContextToPrivateRoom($current_context->getItemID());
				}
			}
			
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();

			$current_iid = $form_data['iid'];
			
			$discarticle_manager = $this->_environment->getDiscussionArticleManager();
			
			if($current_iid !== "NEW") {
				$discarticle_item = $discarticle_manager->getItem($current_iid);
			}

			// check access rights
			if($current_context->isProjectRoom() && $current_context->isClosed()) {
				/*
				 * $params = array();
				   $params['environment'] = $environment;
				   $params['with_modifying_actions'] = true;
				   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
				   unset($params);
				   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
				   $page->add($errorbox);
				 */
			} elseif($current_iid !== 'NEW' && !isset($discarticle_item)) {
				/*
				 * $params = array();
				   $params['environment'] = $environment;
				   $params['with_modifying_actions'] = true;
				   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
				   unset($params);
				   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
				   $page->add($errorbox);
				 */
			} elseif(	!(($current_iid === 'NEW' && $current_user->isUser()) ||
						($current_iid !== 'NEW' && isset($discarticle_item) && $discarticle_item->mayEdit($current_user)))) {
				/*
				 * $params = array();
				   $params['environment'] = $environment;
				   $params['with_modifying_actions'] = true;
				   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
				   unset($params);
				   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
				   $page->add($errorbox);
				 */
			}

			// access granted
			else {
				// save item
				if($this->_popup_controller->checkFormData()) {
					
					// if item id is "NEW" create a new discussion article
					if($current_iid === "NEW") {
						$discarticle_item = $discarticle_manager->getNewItem();
						$discarticle_item->setContextID($this->_environment->getCurrentContextID());
						$discarticle_item->setCreatorItem($current_user);
						$discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
						$discarticle_item->setDiscussionID($additional["discussionId"]);
						
						// if answerTo is set, this article belongs to a threaded discussion and the correct position needs to be set
						if(isset($additional["answerTo"]) && $additional["answerTo"] !== null && !empty($additional["answerTo"])) {
							$answerTo = $additional["answerTo"];
						
							$discussionManager = $this->_environment->getDiscussionManager();
							$discussionItem = $discussionManager->getItem($additional["discussionId"]);
							
							// get the position of the discussion article this is a response to
							$answerToItem = $discarticle_manager->getItem($answerTo);
							$answerToPosition = $answerToItem->getPosition();
							
							// load discussion articles
							$discarticle_manager->reset();
							
							$discarticle_manager->setDiscussionLimit($additional["discussionId"], "");
							$discarticle_manager->select();
							
							$discussionArticlesList = $discarticle_manager->get();
							
							// build an array with all positions > $answerToPosition
							$positionArray = array();
							$discussionArticle = $discussionArticlesList->getFirst();
							while ($discussionArticle) {
								$articlePosition = $discussionArticle->getPosition();
								
								if ($articlePosition > $answerToPosition) {
									$positionArray[] = $articlePosition;
								}
								
								$discussionArticle = $discussionArticlesList->getNext();
							}
							sort($positionArray);
							
							// check if there is at least one direct answer to the $answerToItem
							$hasChild = in_array($answerToPosition . ".1001", $positionArray);
							
							// if there is none, this article will be the first child
							if (!$hasChild) {
								$discarticle_item->setPosition($answerToPosition . ".1001");
							}
							
							// otherwise we need do determ the correct position for appending
							else {
								// explode all sub-positions
								$answerToPositionArray = explode(".", $answerToPosition);
								
								$compareArray = array();
								$end = count($positionArray) - 1;
								for ($i = 0; $i <= $end; $i++) {
									$valueArray = explode(".", $positionArray[$i]);
									
									$in = true;
									$end2 = count($answerToPositionArray) - 1;
									for ($j = 0; $j <= $end2; $j++) {
										if (isset($valueArray[$j]) && $answerToPositionArray[$j] != $valueArray[$j]) {
											$in = false;
										}
									}
									
									if ($in && count($valueArray) == count($answerToPositionArray) + 1) {
										$compareArray[] = $valueArray[count($answerToPositionArray)];
									}
								}
								
								$length = count($compareArray) - 1;
								$result = $compareArray[$length];
								$endResult = $result + 1;
								
								$discarticle_item->setPosition($answerToPosition . "." . $endResult);
							}
						} else {
							$discarticle_item->setPosition("1");
						}
					}
					
					// set modificator and modification date
					$discarticle_item->setModificatorItem($current_user);
					$discarticle_item->setModificationDate(getCurrentDateTimeInMySQL());

					// set attributes
					if(isset($form_data['title'])) $discarticle_item->setSubject($form_data['title']);
					if(isset($form_data['description'])) $discarticle_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));

		            // already attached files
		            $file_ids = array();
		            foreach($form_data as $key => $value) {
		            	if(mb_substr($key, 0, 5) === 'file_') {
		            		$file_ids[] = $value;
		            	}
		            }
		            
		            // this will handle already attached files as well as adding new files
		            $this->_popup_controller->getUtils()->setFilesForItem($discarticle_item, $file_ids, $form_data["files"]);
		            

					// save item
					$discarticle_item->save();

					$this->_return = $discarticle_item->getItemID();

					// set return
					$this->_popup_controller->setSuccessfullItemIDReturn($discarticle_item->getDiscussionID());
				}
			}
		}

		public function getFieldInformation($sub = '') {
			return array(
				array(	'name'		=> 'title',
						'type'		=> 'text',
						'mandatory' => true),
				array(	'name'		=> 'description',
						'type'		=> 'text',
						'mandatory'	=> false)
			);
		}

		public function cleanup_session($current_iid) {
		}

		private function assignTemplateVars() {
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();

			// general information
			$general_information = array();

			// max upload size
			$val = $current_context->getMaxUploadSizeInBytes();
			$meg_val = round($val / 1048576);
			$general_information['max_upload_size'] = $meg_val;

			$this->_popup_controller->assign('popup', 'general', $general_information);

			// user information
			$user_information = array();
			$user_information['fullname'] = $current_user->getFullName();
			$this->_popup_controller->assign('popup', 'user', $user_information);


			// config information
			$config_information = array();
			$config_information['with_activating'] = $current_context->withActivatingContent();
			$this->_popup_controller->assign('popup', 'config', $config_information);
		}
	}