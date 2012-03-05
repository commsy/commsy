<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_institution_detail_controller extends cs_detail_controller {		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'institution_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_INSTITUTION_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			$session = $this->_environment->getSessionItem();
			
			// try to set the item
			$this->setItem();
			
			$this->setupInformation();
			
			//include_once('include/inc_delete_entry.php');
			
			// check for item type
			if($this->_item->getItemType() !== CS_INSTITUTION_TYPE) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				// init
				$current_user = $this->_environment->getCurrentUserItem();
				$context_item = $this->_environment->getCurrentContextItem();
				
				// used to signal which "creator infos" of annotations are expanded...
				$creatorInfoStatus = array();
				if(!empty($_GET['creator_info_max'])) {
					$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
				}
				
				
				
				/*
				 * 

				   // initialize objects
				   $current_context = $environment->getCurrentContextItem();
				   $params = array();
				   $params['environment'] = $environment;
				   $params['with_modifying_actions'] = $current_context->isOpen();
				   $params['creator_info_status'] = $creatorInfoStatus;
				   $detail_view = $class_factory->getClass(INSTITUTION_DETAIL_VIEW,$params);
				   unset($params);
				   */
				
				// check for deleted
				if($this->_item->isDeleted()) {
					throw new cs_detail_item_type_exception('item deleted', 1);
				}
				
				// check for visibility
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
					// enter or leave institution
					if(!empty($_GET['institution_option'])) {
						if($_GET['institution_option'] === '1') {
							$this->_item->addMember($current_user);
						} elseif($_GET['institution_option'] === '2') {
							$this->_item->removeMember($current_user);
						}
					}
					
					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();
					
					// set rubric connections
					$current_room_modules = $context_item->getHomeConf();
					$room_modules = explode(',', $current_room_modules);
					
					$first = array();
					$second = array();
					foreach($room_modules as $module) {
						list($name, $view) = explode('_', $module);
						
						if($view != 'none' && $name != $_GET['mod'] && $name != CS_USER_TYPE) {
							switch($this->isPerspective($name)) {
								case true:
									$first[] = $name;
									break;
								case false:
									$second[] = $name;
									break;
							}
						}
					}
					
					$room_modules = array_merge($first, $second);
					$rubric_connections = array();
					foreach($room_modules as $module) {
						if($context_item->withRubric($module)) {
							$ids = $this->_item->getLinkedItemIDArray($module);
							$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_' . $module . '_index_ids', $ids);
							$rubric_connections[] = $module;
						}
					}
					
					$this->setRubricConnections($rubric_connections);
				}
			}
			
			$this->assign('detail', 'content', $this->getDetailContent());
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();
			
			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_institution_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_institution_index_ids'));
			}
		}
		
		protected function getAdditionalActions(&$perms) {
			/*TODO:
			 * $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();

      $html  = '';
      $html .= $this->_getEditAction($item,$current_user);
      $html .= $this->_getDetailItemActionsAsHTML($item).'&nbsp;&nbsp;&nbsp;';
      $html .= $this->_getPrintAction($item,$current_user);
      $html .= $this->_getMailAction($item,$current_user,type2Module(CS_INSTITUTION_TYPE));
      $html .= $this->_getDownloadAction($item,$current_user);
      $html .= $this->_getNewAction($item,$current_user);

      $html .= $this->_initDropDownMenus();
			 */
			
			
			/* TODO: this is for sub items
			 * $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $context_item = $this->_environment->getCurrentContextItem();
      if ( $item->isMember($current_user) ) {
         if ( !$item->isSystemLabel() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $this->_item->getItemID();
            $params['institution_option'] = '2';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TOPIC_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TOPIC_LEAVE').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_INSTITUTION_TYPE,
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TOPIC_LEAVE')).LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('GROUP_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('GROUP_LEAVE').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('TOPIC_LEAVE').' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         if ( !$item->isSystemLabel() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $this->_item->getItemID();
            $params['institution_option'] = '1';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TOPIC_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TOPIC_ENTER').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_INSTITUTION_TYPE,
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TOPIC_ENTER')).LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TOPIC_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TOPIC_ENTER').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('TOPIC_ENTER').' "class="disabled">'.$image.'</a>'.LF;
         }
      }

      // delete
      $html .= $this->_getDeleteAction($item,$current_user);

      return $html;
			 */
		}
		
		protected function getDetailContent() {
			$converter = $this->_environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$user = $this->_environment->getCurrentUser();
			
			$return = array();
			
			// title
			$return['title'] = $this->_item->getTitle();
			
			// picture
			$picture = $this->_item->getPicture();
			if(!empty($picture)) {
				$disc_manager = $this->_environment->getDiscManager();
				if($disc_manager->existsFile($picture)) {
					$return['picture'] = $picture;
				}
			}
			
			// description
			$desc = $this->_item->getDescription();
			if(!empty($desc)) {
				$desc = $converter->cleanDataFromTextArea($desc);
				//TODO:
				//$desc = $converter->compareWithSearchText($desc);
				$converter->setFileArray($this->getItemFileList());
				$desc = $converter->text_as_html_long($desc);
				$return['description'] = $desc;
				//$html .= $this->getScrollableContent($desc,$item,'',true).LF;
			}
			
			// members
			$members = $this->_item->getMemberItemList();
			$members_array = array();
			if(!$members->isEmpty()) {
				$member = $members->getFirst();
				
				while($member) {
					$member_array = array();
					
					if($member->isUser()) {
						$linktext = $member->getFullname();
						//TODO: compareWithSearchText
						
						$member_title = $member->getTitle();
						if(!empty($member_title)) {
							$linktext .= ', ' . $member_title;
							//TODO: compareWithSearchText
						}
						
						$member_array['linktext'] = $converter->text_as_html_short($linktext);
						
						if($member->maySee($user)) {
							$member_array['may_see'] = true;
							$member_array['iid'] = $member->getItemID();
						} else {
							$member_array['may_see'] = false;
							
							if($user->isGuest() && $member->isVisibleForLoggedIn()) {
								$member_array['visible'] = true;
							} else {
								$member_array['visible'] = false;
							}
						}
					}
					
					$members_array[] = $member_array;
					$member = $members->getNext();
				}
				
				$return['members'] = $members_array;
			}
			
			return $return;
		}
	}