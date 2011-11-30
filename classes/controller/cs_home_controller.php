<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_home_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'room_home';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		public function actionIndex() {
			$this->assign('room', 'home_content', $this->getContentForHomeList());
		}

		/**
		* gets information for displaying the content in home rubric
		*/
		private function getContentForHomeList() {
			$rubrics = $this->getRubrics();
			$rubric_list = array();

			// determe rubrics to show on home list
			foreach($rubrics as $rubric) {
				list($rubric_name, $postfix) = explode('_', $rubric);

				// continue if postfix is none or nodisplay
				if($postfix === 'none' || $postfix === 'nodisplay') continue;

				// TODO: where does activity come from?
				// continue if name of rubric is activity
				if($rubric_name === 'activity') continue;

				$rubric_list[] = $rubric_name;
			}

			// get list information
			return $this->getListContent($rubric_list, CS_HOME_RUBRIC_LIST_LIMIT);
		}
		
		public function getListContent() {
			$session = $this->_environment->getSessionItem();
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$current_user = $environment->getCurrentUser();
			
			$rubrics = $this->getRubrics();
			$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
			
			foreach ( $rubrics as $rubric ) {
	         $rubric_array = explode('_', $rubric);
	         if ( $rubric_array[1] != 'none' and  $rubric_array[1] != 'nodisplay') {
	            if ( $rubric_array[0] != 'activity') {
	               $list = new cs_list();
	               $rubric = '';
	               $param_class_array = array();
	               $param_class_array['environment'] = $environment;
	               $param_class_array['with_modifying_actions'] = $context_item->isOpen();
	               switch ($rubric_array[0]){
	                  case CS_ANNOUNCEMENT_TYPE:
	                        $manager = $environment->getAnnouncementManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        $manager->setDateLimit(getCurrentDateTimeInMySQL());
	                        $manager->setSortOrder('modified');
	                        $manager->showNoNotActivatedEntries();
	
	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
	
	                        $manager->select();
	                        $list = $manager->get();
	                     break;
	                  case CS_DATE_TYPE:
	                        $manager = $environment->getDatesManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->setDateModeLimit(2);
	                        $count_all = $manager->getCountAll();
	                        $manager->setFutureLimit();
	                        $manager->setDateModeLimit(3);
	                        $manager->showNoNotActivatedEntries();
	
	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
	
	                        $manager->select();
	                        $list = $manager->get();
	                        $rubric = 'dates';
	                     break;
	                  case CS_PROJECT_TYPE:
	                        $room_type = CS_PROJECT_TYPE;
	                        $manager = $environment->getProjectManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentPortalID());
	                        if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr  ) {
	                           $manager->setCommunityRoomLimit($environment->getCurrentContextID());
	                        } else {
	                           # use redundant infos in community room
	                           $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
	                        }
	                        $count_all = $manager->getCountAll();
	                        $manager->setSortOrder('activity_rev');
	                        if ( $interval > 0 ) {
	                           $manager->setIntervalLimit(0,5);
	                        }
	                        $manager->select();
	                        $list = $manager->get();
	                     break;
	                  case CS_GROUP_TYPE:
	                        $manager = $environment->getGroupManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->select();
	                        $list = $manager->get();
	                        $count_all = $list->getCount();
	                     break;
	                  case CS_TODO_TYPE:
	                        $manager = $environment->getTodoManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        $manager->setStatusLimit(4);
	                        $manager->setSortOrder('date');
	                        $manager->showNoNotActivatedEntries();
	
	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
	
	                        $manager->select();
	                        $list = $manager->get();
	                        $item = $list->getFirst();
	                        $tmp_id_array = array();
	                        while ($item){
	                           $tmp_id_array[] = $item->getItemID();
	                           $item = $list->getNext();
	                        }
	                        $step_manager = $environment->getStepManager();
	                        $step_list = $step_manager->getAllStepItemListByIDArray($tmp_id_array);
	                        $item = $step_list->getFirst();
	                        while ($item){
	                           $sub_id_array[] = $item->getItemID();
	                           $item = $step_list->getNext();
	                        }
	                        unset($step_list);
	                        unset($step_manager);
	                        unset($manager);
	                        break;
	                  case CS_TOPIC_TYPE:
	                        $manager = $environment->getTopicManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->select();
	                        $list = $manager->get();
	                        $count_all = $list->getCount();
	                     break;
	                  case CS_INSTITUTION_TYPE:
	                        $manager = $environment->getInstitutionManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->select();
	                        $list = $manager->get();
	                        $count_all = $list->getCount();
	                     break;
	                  case CS_USER_TYPE:
	                        $manager = $environment->getUserManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $manager->setUserLimit();
	                        $count_all = $manager->getCountAll();
	                        if (!$current_user->isGuest()){
	                           $manager->setVisibleToAllAndCommsy();
	                        } else {
	                           $manager->setVisibleToAll();
	                        }
	                        $manager->setAgeLimit($context_item->getTimeSpread());
	                        $manager->select();
	                        $list = $manager->get();
	                     break;
	                  case CS_MATERIAL_TYPE:
	                        $short_list_view = $class_factory->getClass(MATERIAL_SHORT_VIEW,$param_class_array);
	                        $manager = $environment->getMaterialManager();
	                        $manager->reset();
	                        $manager->create_tmp_table($environment->getCurrentContextID());
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        $manager->setOrder('date');
	                        if ($environment->inProjectRoom()){
	                           $manager->setAgeLimit($context_item->getTimeSpread());
	                        } else {
	                           $manager->setIntervalLimit(0,5);
	                           $home_rubric_limit = 5;
	                        }
	                        $manager->showNoNotActivatedEntries();
	
	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
	
	                        $manager->select();
	                        $list = $manager->get();
	                        $manager->delete_tmp_table();
	                        $short_list_view->setList($list);
	                        $short_list_view->setCountAll($count_all);
	                        $item = $list->getFirst();
	                        $tmp_id_array = array();
	                        while ($item){
	                           $tmp_id_array[] = $item->getItemID();
	                           $item = $list->getNext();
	                        }
	                        $section_manager = $environment->getSectionManager();
	                        $section_list = $section_manager->getAllSectionItemListByIDArray($tmp_id_array);
	                        $item = $section_list->getFirst();
	                        while ($item){
	                           $sub_id_array[] = $item->getItemID();
	                           $v_id_array[$item->getItemID()] = $item->getVersionID();
	                           $item = $section_list->getNext();
	                        }
	                     break;
	                  case CS_DISCUSSION_TYPE:
	                        $manager = $environment->getDiscussionManager();
	                        $manager->reset();
	                        $manager->setContextLimit($environment->getCurrentContextID());
	                        $count_all = $manager->getCountAll();
	                        if ($environment->inProjectRoom() or $environment->inGroupRoom() ) {
	                           $manager->setAgeLimit($context_item->getTimeSpread());
	                        } elseif ($environment->inCommunityRoom()) {
	                           $manager->setIntervalLimit(0,5);
	                           $home_rubric_limit = 5;
	                        }
	                        $manager->showNoNotActivatedEntries();
	
	                        $count_select = $manager->getCountAll();
	                        $manager->setIntervalLimit(0, $home_rubric_limit);
	                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
	                        
	                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
	
	                        $manager->select();
	                        $list = $manager->get();
	                        $item = $list->getFirst();
	                        $disc_id_array = array();
	                        while ($item){
	                           $disc_id_array[] = $item->getItemID();
	                           $item = $list->getNext();
	                        }
	                        $discarticle_manager = $environment->getDiscussionArticleManager();
	                        $discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($disc_id_array);
	                        $item = $discarticle_list->getFirst();
	                        while ($item){
	                           $disc_id_array[] = $item->getItemID();
	                           $item = $discarticle_list->getNext();
	                        }
	                     break;
                  }
                  
                  $tmp = $list->getFirst();
                  $ids = array();
                  while ($tmp){
	                  $id_array[] = $tmp->getItemID();
	                  if ($rubric_array[0] == CS_MATERIAL_TYPE){
	                     $v_id_array[$tmp->getItemID()] = $item->getVersionID();
	                  }
	                  $ids[] = $tmp->getItemID();
	                  $tmp = $list->getNext();
	               }
	               if (empty($rubric)){
	                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric_array[0].'_index_ids', $ids);
	               }else{
	                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $ids);
	               }
	               
	               $noticed_manager = $environment->getNoticedManager();
			      $id_array = array_merge($id_array, $disc_id_array);
			      $noticed_manager->getLatestNoticedByIDArray($id_array);
			      $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
			      $id_array = array_merge($id_array, $sub_id_array);
			      $link_manager = $environment->getLinkManager();
			      $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array, $v_id_array);
			      $file_manager = $environment->getFileManager();
			      $file_manager->setIDArrayLimit($file_id_array);
	               
	           		 while($item) {
						$item_array[] = array(
						'iid'				=> $item->getItemID(),
						'title'				=> $view->_text_as_html_short($item->getTitle()),
						'modification_date'	=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
						'creator'			=> $item->getCreatorItem()->getFullName(),
						'attachment_count'	=> $item->getFileList()->getCount()
		//				'attachment_infos'	=>
						);
		
						$item = $list->getNext();
					}
	               
	            }
	         }
	      }

			
					// append return
					$return = array(
						'items'		=> $item_array,
						'count_all'	=> $count_all_shown
					);
		}
	}