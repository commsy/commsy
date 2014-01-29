<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_announcement_detail_controller extends cs_detail_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'announcement_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_ANNOUNCEMENT_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			$session = $this->_environment->getSessionItem();

			// try to set the item
			$this->setItem();

			$this->setupInformation();

			// used to signal which "creator ifnos" of annotations are expanded...
			$creatorInfoStatus = array();
			if(!empty($_GET['creator_info_max'])) {
				$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
			}

			// TODO: implement deletion handling
			//include_once('include/inc_delete_entry.php');

			// check for item type
			$item_manager = $this->_environment->getItemManager();
			$type = $item_manager->getItemType($_GET['iid']);
			if($type !== CS_ANNOUNCEMENT_TYPE) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				$current_context = $this->_environment->getCurrentContextItem();
				$current_user = $this->_environment->getCurrentUser();

				/*
				$params = array();
			   $params['environment'] = $environment;
			   $params['with_modifying_actions'] = $current_context->isOpen();
			   $params['creator_info_status'] = $creatorInfoStatus;
			   $detail_view = $class_factory->getClass(ANNOUNCEMENT_DETAIL_VIEW,$params);
			   unset($params);
			    */

				// check if item exists
				if($this->_item === null) {
					include_once('functions/error_functions.php');
      				trigger_error('Item ' . $_GET['iid'] . ' does not exist!', E_USER_ERROR);
				}

				// check if item is deleted
				elseif($this->_item->isDeleted()) {
					throw new cs_detail_item_type_exception('item deleted', 1);
				}

				// check for access rights
				elseif(!$this->_item->maySee($current_user)) {
					// TODO: implement error handling
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
					 */
				} else {
					// get clipboard
					$clipboard_id_array = array();
					if($session->issetValue('announcement_clipboard')) {
						$clipboard_id_array = $session->getValue('announcement_clipboard');
					}

					// copy to clipboard
					if(isset($_GET['add_to_announcement_clipboard']) && !in_array($current_item_id, $clipboard_id_array)) {
						$clipboard_id_array[] = $current_item_id;
						$session->setValue('announcement_clipboard', $clipboard_id_array);
					}

					// set clipboard ids
					$this->setClipboardIDArray($clipboard_id_array);

					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();

					$announcement_ids = array();
					if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_announcement_index_ids')) {
						$announcement_ids = $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_announcement_index_ids');
					}

					$current_room_modules = $current_context->getHomeConf();
					if(!empty($current_room_modules)) {
						$room_modules = explode(',', $current_room_modules);
					} else {
						// this seems to be never set before
						//$room_modules = $default_room_modules;
					}

					$first = array();
					$second = array();

					foreach($room_modules as $module) {
						list($module_name, $display_mode) = explode('_', $module);

						if($display_mode !== 'none' && $module_name !== $this->_environment->getCurrentModule()) {
							if($this->isPerspective($module_name) === true) {
								$first[] = $module_name;
							} else {
								$second[] = $module_name;
							}
						}
					}

					$room_modules = array_merge($first, $second);
					$rubric_conntections = array();
					foreach($room_modules as $module) {
						if($current_context->withRubric($module)) {
							$ids = $this->_item->getLinkedItemIDArray($module);
							$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $module . '_index_ids', $ids);
							// never used again...
							//$rubric_connections[] = $module;
						}
					}

					// seems to be not needed
					//$detail_view->setRubricConnections($announcement_item);

					// annotations
					// get annotations
					$annotations = $this->_item->getAnnotationList();

					// assign annotations
					$this->assign('detail', 'annotations', $this->getAnnotationInformation($annotations));

					/*
					 *TODO: handle in smarty as post_filter
				      // highlight search words in detail views
				      $session_item = $environment->getSessionItem();
				      if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
				         $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
				         if ( !empty($search_array['search']) ) {
				            $detail_view->setSearchText($search_array['search']);
				         }
				         unset($search_array);
				      }


			      $page->add($detail_view);
					 */

					// assessment
					$this->assign('detail', 'assessment', $this->getAssessmentInformation($this->_item));
					$this->assign('detail', 'content', $this->getDetailContent());
				}
			}
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/

		protected function getAdditionalActions(&$perms) {

		}

		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();

			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_announcement_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_announcement_index_ids'));
			}
		}



		private function getFormalData() {
			$return = array();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			$context_item = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			$formal_data = array();
		    if ($this->_item->isNotActivated()){
		        $activating_date = $this->_item->getActivatingDate();
		        $text = '';
		        if (strstr($activating_date,'9999-00-00')){
		           $activating_text = $translator->getMessage('COMMON_NOT_ACTIVATED');
		        }else{
		           $activating_text = $translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($this->_item->getActivatingDate());
		        }
				$temp_array = array();
				$temp_array[] = $translator->getMessage('COMMON_RIGHTS');
				$temp_array[] = $activating_text;
				$return[] = $temp_array;
		    }
		    $temp_array = array();
			$temp_array[0] = $translator->getMessage('ANNOUNCEMENT_SHOW_HOME_DATE');
			$temp_array[1] = getDateTimeInLang($this->_item->getSeconddateTime());
			$return[] = $temp_array;

			return $return;
		}


		protected function getDetailContent() {
			$converter = $this->_environment->getTextConverter();
			$desc = $this->_item->getDescription();
			if(!empty($desc)) {
				$converter->setFileArray($this->getItemFileList());
				
	      		if ( $this->_with_old_text_formating ) {
	      			$desc = $converter->textFullHTMLFormatting($desc);
	      		} else {
					   //$desc = $converter->cleanDataFromTextArea($desc);
					   //$desc = $converter->compareWithSearchText...
					   //$desc = $converter->text_as_html_long($desc);
					   //$desc = $converter->show_images($desc, $this->_item, true);
					   //$html .= $this->getScrollableContent($desc,$item,'',true);
					   $desc = $converter->textFullHTMLFormatting($desc);
	      		}
			}

			return array(
				'item_id'		=> $this->_item->getItemID(),
				'formal'		=> $this->getFormalData(),
                'files'			=> $this->getFileContent(),
				'title'			=> $this->_item->getTitle(),
				'creator'		=> $this->_item->getCreatorItem()->getFullName(),
				'creation_date'	=> getDateTimeInLang($this->_item->getCreationDate()),
				'description'	=> $desc,
				'moredetails'	=> $this->getCreatorInformationAsArray($this->_item)
			);
		}
	}