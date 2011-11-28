<?php
	require_once('classes/controller/cs_room_controller.php');
	
	abstract class cs_list_controller extends cs_room_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();
		}
		
		/**
		 * returns an array, containing the requested list information
		 * @param $rubric_array - item types
		 * @param $limit - optional limit per rubric
		 */
		protected function getListContent($rubric_array, $limit = 0) {
			$list = new cs_list();
			$return = array();
			
			// check limit
			//if($limit < )
			
			foreach($rubric_array as $rubric) {
				$count_all = 0;
				
				switch($rubric) {
					case CS_ANNOUNCEMENT_TYPE:
						$manager = $this->_environment->getAnnouncementManager();
						$manager->reset();
						$manager->setContextLimit($this->_environment->getCurrentContextID());
						$count_all = $manager->getCountAll();
						//$manager->setDateLimit(getCurrentDateTimeInMySQL());
						$manager->setSortOrder('modified');
						$manager->showNoNotActivatedEntries();		
						$manager->setIntervalLimit(0, $limit);
						
						//if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
				
						$manager->select();
						$list = $manager->get();
						break;
					
					case CS_DATE_TYPE:
						$manager = $this->_environment->getDatesManager();
						$manager->reset();
						$manager->setContextLimit($this->_environment->getCurrentContextID());
						$manager->setDateModeLimit(2);
						$count_all = $manager->getCountAll();
						$manager->setFutureLimit();
						$manager->setDateModeLimit(3);
						$manager->showNoNotActivatedEntries();
						
						$manager->setIntervalLimit(0, $limit);
						//if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
							
						$manager->select();
						$list = $manager->get();
						break;
						/*
					case CS_PROJECT_TYPE:
						$room_type = CS_PROJECT_TYPE;
						$short_list_view = $class_factory->getClass(PROJECT_SHORT_VIEW,$param_class_array);
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
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
						break;
					case CS_GROUP_TYPE:
						$short_list_view = $class_factory->getClass(GROUP_SHORT_VIEW,$param_class_array);
						$manager = $environment->getGroupManager();
						$manager->reset();
						$manager->setContextLimit($environment->getCurrentContextID());
						$manager->select();
						$list = $manager->get();
						$count_all = $list->getCount();
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
						break;
					case CS_TODO_TYPE:
						$short_list_view = $class_factory->getClass(TODO_SHORT_VIEW,$param_class_array);
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
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
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
						$short_list_view = $class_factory->getClass(TOPIC_SHORT_VIEW,$param_class_array);
						$manager = $environment->getTopicManager();
						$manager->reset();
						$manager->setContextLimit($environment->getCurrentContextID());
						$manager->select();
						$list = $manager->get();
						$count_all = $list->getCount();
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
						break;
					case CS_INSTITUTION_TYPE:
						$short_list_view = $class_factory->getClass(INSTITUTION_SHORT_VIEW,$param_class_array);
						$manager = $environment->getInstitutionManager();
						$manager->reset();
						$manager->setContextLimit($environment->getCurrentContextID());
						$manager->select();
						$list = $manager->get();
						$count_all = $list->getCount();
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
						break;
					case CS_USER_TYPE:
						$short_list_view = $class_factory->getClass(USER_SHORT_VIEW,$param_class_array);
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
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
						break;
					*/
					case CS_MATERIAL_TYPE:
						$manager = $this->_environment->getMaterialManager();
						$manager->reset();
						$manager->create_tmp_table($this->_environment->getCurrentContextID());
						$manager->setContextLimit($this->_environment->getCurrentContextID());
						$count_all = $manager->getCountAll();
						$manager->setOrder('date');
						if ($this->_environment->inProjectRoom()){
							$manager->setAgeLimit($this->_environment->getCurrentContextItem()->getTimeSpread());
						} else {
							$manager->setIntervalLimit(0,5);
							$home_rubric_limit = 5;
						}
						$manager->showNoNotActivatedEntries();
						$manager->setIntervalLimit(0, $limit);
						$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
							
						//if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
							
						$manager->select();
						$list = $manager->get();
						$manager->delete_tmp_table();
						/*
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
						*/
						break;
						
					/*
					case CS_DISCUSSION_TYPE:
						$short_list_view = $class_factory->getClass(DISCUSSION_SHORT_VIEW,$param_class_array);
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
						$short_list_view->setList($list);
						$short_list_view->setCountAll($count_all);
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
						
						/*
						unset($param_class_array);
						$item = $list->getFirst();
						$ids = array();
						while ($item){
							$id_array[] = $item->getItemID();
							if ($rubric_array[0] == CS_MATERIAL_TYPE){
								$v_id_array[$item->getItemID()] = $item->getVersionID();
							}
							$ids[] = $item->getItemID();
							$item = $list->getNext();
						}
						if (empty($rubric)){
							$session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric_array[0].'_index_ids', $ids);
						}else{
							$session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $ids);
						}
						$page->addLeft($short_list_view);
						*/
				}
			
				// prepare item array
				$item = $list->getFirst();
				$item_array = array();
				while($item) {
					$item_array[] = array(
					'title'				=> $item->getTitle(),
					'modification_date'	=> $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate()),
					'creator'			=> $item->getCreatorItem()->getFullName()
										
					//$this->_text_as_html_short($this->_translator->getDateInLang($item->getModificationDate()))
					);
					
					$item = $list->getNext();
				}
				
				// append return
				$return[$rubric] = array(
					'items'		=> $item_array,
					'count_all'	=> $count_all
				);
				
				// reset list and item_array
				// TODO list->reset() does not work
				$list = new cs_list();
				$item_array = array();
			}
			
			return $return;
		}
	}