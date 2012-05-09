<?php
	require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');
	
	class cs_popup_discussion_controller implements cs_rubric_popup_controller {
		private $_environment = null;
		private $_popup_controller = null;
		private $_return = '';
		
		/**
		* constructor
		*/
		public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
			$this->_environment = $environment;
			$this->_popup_controller = $popup_controller;
		}
		
		public function initPopup($item) {
			// assign template vars
			$this->assignTemplateVars();
			
			if($item !== null) {
				// edit mode
				$current_context = $this->_environment->getCurrentContextItem();
				
				// TODO: check rights
				
				$this->_popup_controller->assign('item', 'title', $item->getTitle());
				
				$activating = false;
				if($current_context->withActivatingContent()) {
					$activating = true;
					
					$this->_popup_controller->assign('item', 'private_editing', $item->isPrivateEditing());
						
					if($item->isNotActivated()) {
						$this->_popup_controller->assign('item', 'is_not_activated', true);
				
						$activating_date = $item->getActivatingDate();
				
						$this->_popup_controller->assign('item', 'activating_date', mb_substr($activating_date, 0, 10));
						$this->_popup_controller->assign('item', 'activating_time', mb_substr($activating_date, -8));
					}
				}
				
				$this->_popup_controller->assign('popup', 'activating', $activating);
					
				/*
				
				if ( $context_item->withActivatingContent() ) {
				$html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_SHOW_ACTIVATING_ENTRIES').'<br />'.LF;
				
				$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selactivatingstatus" size="1" id="submit_form">'.LF;
				$html .= '      <option value="1"';
				if ( isset($this->_activation_limit) and $this->_activation_limit == 1 ) {
				$html .= ' selected="selected"';
				}
				$html .= '>*'.$this->_translator->getMessage('COMMON_ALL_ENTRIES').'</option>'.LF;
				 
				$html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
				 
				$html .= '      <option value="2"';
				if ( !isset($this->_activation_limit) || $this->_activation_limit == 2 ) {
				$html .= ' selected="selected"';
				}
				$html .= '>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</option>'.LF;
				$html .= '   </select>'.LF;
				$html .='</div>';
				}
				return $html;
				*/
			}
		}
		
		public function edit($item_id) {
			
		}
		
		public function save($form_data, $additional = array()) {
			
			/*
				 * 
				 * // Linked item from "NEW" dropdown-menu
	if(isset($_GET['linked_item'])){
	   $entry_new_array = array();
	   $entry_new_array[] = $_GET['linked_item'];
	   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$entry_new_array);
	}
	if(isset($_GET['mylist_id'])){
	   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id',$_GET['mylist_id']);
	}
	
	// Function used for redirecting to connected rubrics
	if (isset($_GET['return_attach_buzzword_list'])){
	   $_POST = $session->getValue('buzzword_post_vars');
	   unset($_POST['option']);
	   unset($_POST['right_box_option']);
	}
	if (isset($_GET['return_attach_tag_list'])){
	   $_POST = $session->getValue('tag_post_vars');
	   unset($_POST['option']);
	   unset($_POST['right_box_option']);
	}
	if (isset($_GET['return_attach_item_list'])){
	   $_POST = $session->getValue('linked_items_post_vars');
	   unset($_POST['option']);
	   unset($_POST['right_box_option']);
	}
	// Function used for cleaning up the session. This function
	// deletes ALL session variables this page writes.
	function cleanup_session ($current_iid) {
	   global $session,$environment;
	   $session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
	   $session->unsetValue($environment->getCurrentModule().'_add_tags');
	   $session->unsetValue($environment->getCurrentModule().'_add_files');
	   $session->unsetValue($current_iid.'_post_vars');
	}
			 */ 
	
			
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
			
			$current_iid = $form_data['iid'];
			
			//$with_anchor = false;
			
			/*
	
	// Get the translator object
	$translator = $environment->getTranslationObject();
	
	
	// Coming back from attaching something
	if ( !empty($_GET['backfrom']) ) {
	   $backfrom = $_GET['backfrom'];
	} else {
	   $backfrom = false;
	}*/
			if($current_iid === 'NEW') {
				$discussion_item = null;
			} else {
				$discussion_manager = $this->_environment->getDiscussionManager();
				$discussion_item = $discussion_manager->getItem($current_iid);
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
			} elseif(	!(($current_iid === 'NEW' && $current_user->isUser()) ||
						($current_iid !== 'NEW' && isset($discussion_item) &&
						$discussion_item->mayEditIgnoreClose($current_user)))) {
				/*
				 *    $discussion_item->mayEditIgnoreClose($current_user))) ) {
			$params = array();
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
				$this->cleanup_session($current_iid);
				
				// save item
				if($this->_popup_controller->checkFormData()) {
					$session = $this->_environment->getSessionItem();
					
					if($discussion_item === null) {
						$discussion_manager = $this->_environment->getDiscussionManager();
						$discussion_item = $discussion_manager->getNewItem();
						$discussion_item->setContextID($this->_environment->getCurrentContextID());
						$discussion_item->setCreatorItem($current_user);
						$discussion_item->setCreationDate(getCurrentDateTimeInMySQL());
					}
					
					$discussion_item->setModificatorItem($current_user);
					
					// set attributes
					if(isset($form_data['title'])) $discussion_item->setTitle($form_data['title']);
					
					if(isset($form_data['public'])) {
						if($discussion_item->isPublic() != $form_data['public']) {
							$discussion_item->setPublic($form_data['public']);
						}
					} else {
						if(isset($form_data['private_editing'])) {
							$discussion_item->setPrivateEditing('0');
						} else {
							$discussion_item->setPrivateEditing('1');
						}
					}
					
					if(isset($form_data['external_viewer']) && isset($form_data['external_viewer_accounts'])) {
						$user_ids = explode(" ", $form_data['external_viewer_accounts']);
						$discussion_item->setExternalViewerAccounts($user_ids);
					} else {
						$discussion_item->unsetExternalViewerAccounts();
					}
					
					if(isset($form_data['hide'])) {
						// variables for datetime-format of end and beginning
						$dt_hiding_time = '00:00:00';
						$dt_hiding_date = '9999-00-00';
						$dt_hiding_datetime = '';
						$converted_day_start = convertDateFromInput($form_data['dayStart'], $this->_environment->getSelectedLanguage());
						if($converted_day_start['conforms'] === true) {
							$dt_hiding_datetime = $converted_day_start['datetime'] . ' ';
							$converted_time_start = convertTimeFromInput($form_data['timeStart']);
							if ($converted_time_start['conforms'] === true) {
								$dt_hiding_datetime .= $converted_time_start['datetime'];
							} else {
								$dt_hiding_datetime .= $dt_hiding_time;
							}
						} else {
							$dt_hiding_datetime = $dt_hiding_date . ' ' . $dt_hiding_time;
						}
						$discussion_item->setModificationDate($dt_hiding_datetime);
					} else {
						if($discussion_item->isNotActivated()) $discussion_item->setModificationDate(getCurrentDateTimeInMySQL());
					}
					
					// buzzwords
					/*
					 * if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids')){
							$discussion_item->setBuzzwordListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids'));
							$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
						}
					 */
					$discussion_item->setBuzzwordListByID($form_data['buzzwords']);
					
					// tags
					/*
						if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids')){
						$discussion_item->setTagListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids'));
						$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
						}
					 */
					$discussion_item->setTagListByID($form_data['tags']);
					
					/*
						if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
						$discussion_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
						$session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
						}
					*/
					
					// files
					$file_ids = $form_data['files'];
					$this->_popup_controller->getUtils()->setFilesForItem($discussion_item, $file_ids);
	
					// save item
					$discussion_item->save();
					
					$id_array = array();
					if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_' . $this->_environment->getCurrentModule() . '_index_ids')) {
						$id_array = array_reverse($session->getValue('cid' . $this->_environment->getCurrentContextID() . '_' . $this->_environment->getCurrentModule() . '_index_ids'));
					}
					
					$id_array[] = $discussion_item->getItemID();
					$id_array = array_reverse($id_array);
					$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $this->_environment->getCurrentModule() . '_index_ids', $id_array);
					
					// save initial discussion article
					if($current_iid === 'NEW') {
						$discarticle_manager = $this->_environment->getDiscussionArticlesManager();
						$discarticle_item = $discarticle_manager->getNewItem();
						$discarticle_item->setContextID($this->_environment->getCurrentContextID());
						$discarticle_item->setCreatorItem($current_user);
						$discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
						$discarticle_item->setDiscussionID($discussion_item->getItemID());
	
						if(isset($form_data['subject'])) $discarticle_item->setSubject($form_data['subject']);
						if(isset($form_data['description'])) $discarticle_item->setDescription($form_data['description']);
						if(isset($form_data['discussion_type']) && $form_data['discussion_type'] == 2) $discarticle_item->setPosition('1');
	
						$discarticle_item->save();
	
						// update discussion item
						$discussion_item->setLatestArticleID($discarticle_item->getItemID());
						$discussion_item->setLatestArticleModificationDate($discarticle_item->getCreationDate());
	
						$discussion_status = $current_context->getDiscussionStatus();
						if($discussion_status == 3) {
							if($form_data['discussion_type'] == 2) $discussion_item->setDiscussionType('threaded');
							else $discussion_item->setDiscussionType('simple');
						} elseif($discussion_status == 2) {
							$discussion_item->setDiscussionType('threaded');
						} else {
							$discussion_item->setDiscussionType('simple');
						}
	
						$discussion_item->save();
					}
					
					/*
						if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id')){
						$mylist_manager = $environment->getMylistManager();
						$mylist_item = $mylist_manager->getItem($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id'));
						$id_array = $mylist_item->getAllLinkedItemIDArrayLabelVersion();
						if (!in_array($discussion_item->getItemID(),$id_array)){
							$id_array[] =  $discussion_item->getItemID();
						}
						$mylist_item->saveLinksByIDArray($id_array);
						}
						$session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_mylist_id');
	
						// Redirect
						cleanup_session($current_iid);
						$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
						$session->unsetValue('buzzword_post_vars');
						$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
						$session->unsetValue('tag_post_vars');
						$session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
						$session->unsetValue('linked_items_post_vars');
						$params = array();
						$params['iid'] = $discussion_item->getItemID();;
						redirect($environment->getCurrentContextID(),
								'discussion', 'detail', $params);
					 */
					
					$this->_return = $discussion_item->getItemID();
				}
			}
	
				/*
	
	
					// Display form
					$class_params = array();
					$class_params['environment'] = $environment;
					$class_params['with_modifying_actions'] = true;
					$form_view = $class_factory->getClass(FORM_VIEW,$class_params);
					unset($class_params);
					if ($with_anchor){
						$form_view->withAnchor();
					}
					if (!mayEditRegular($current_user, $discussion_item)) {
						$form_view->warnChanger();
						$params = array();
						$params['environment'] = $environment;
						$params['with_modifying_actions'] = true;
						$params['width'] = 500;
						$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
						unset($params);
						$errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
						$page->add($errorbox);
					}
					$form_view->setAction(curl($environment->getCurrentContextID(),'discussion','edit',''));
					$form_view->setForm($form);
					$page->add($form_view);
				}
				}
	?>
				 */
		}
		
		public function getReturn() {
			return $this->_return;
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
		
		private function assignTemplateVars() {
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
			
			// general information
			$general_information = array();
			
			// max upload size
			$val = ini_get('upload_max_filesize');
			$val = trim($val);
			$last = $val[mb_strlen($val) - 1];
			switch($last) {
				case 'k':
				case 'K':
					$val *= 1024;
					break;
				case 'm':
				case 'M':
					$val *= 1048576;
					break;
			}
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
		
		private function cleanup_session($current_iid) {
			$environment = $this->_environment;
			$session = $this->_environment->getSessionItem();
	
			$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
			$session->unsetValue($environment->getCurrentModule().'_add_tags');
			$session->unsetValue($environment->getCurrentModule().'_add_files');
			$session->unsetValue($current_iid.'_post_vars');
		}
	}