<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_list_controller extends cs_room_controller {
		private $_entries_per_page = 20;
		protected $_list_parameter_arrray = array();

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

		protected function getViewMode(){
			$mode = 'browse';
			if ( isset($_GET['mode']) ) {
            	return $_GET['mode'];
			} elseif ( isset($_POST['mode']) ) {
   				return $_POST['mode'];
			} else {
   				unset($this->_list_parameter_arrray['ref_iid']);
   				unset($this->_list_parameter_arrray['ref_user']);
			}
		}


		protected function performListOption($rubric){
			$environment = $this->_environment;
			$session = $environment->getSessionItem();
			$translator = $environment->getTranslationObject();

			// Find current option
			if ( isset($_POST['option']) ) {
   				$option = $_POST['option'];
			} elseif ( isset($_GET['option']) ) {
   				$option = $_GET['option'];
			} else {
   				$option = '';
			}

			// Find out what to do
			if ( isset($_POST['delete_option']) ) {
   				$delete_command = $_POST['delete_option'];
			}elseif ( isset($_GET['delete_option']) ) {
   				$delete_command = $_GET['delete_option'];
			} else {
   				$delete_command = '';
			}

			// LIST ACTIONS
			// initiate selected array of IDs
			$selected_ids = array();
			$mode = $this->getViewMode();
			if ($mode == '') {
   				$session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
			}elseif ($mode == 'list_actions') {
   				if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_selected_ids')) {
      				$selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_selected_ids');
   				}
			}

	      	// Update attached items from cookie (requires JavaScript in browser)
      		if ( isset($_COOKIE['attach']) ) {
         		foreach ( $_COOKIE['attach'] as $key => $val ) {
            		setcookie ('attach['.$key.']', '', time()-3600);
            		if ( $val == '1' ) {
               			if ( !in_array($key, $selected_ids) ) {
                  			$selected_ids[] = $key;
               			}
            		} else {
               			$idx = array_search($key, $selected_ids);
               			if ( $idx !== false ) {
                  			unset($selected_ids[$idx]);
               			}
            		}
         		}
      		}

	      	// Update attached items from form post (works always)
    	  	if ( isset($_POST['attach']) ) {
        	 	foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            		if ( array_key_exists($shown_key, $_POST['attach']) ) {
               			if ( !in_array($shown_key, $selected_ids) ) {
                  			$selected_ids[] = $shown_key;
               			}
	            	} else {
    	           		$idx = array_search($shown_key, $selected_ids);
        	       		if ( $idx !== false ) {
            	      		unset($selected_ids[$idx]);
               			}
            		}
         		}
      		}

			// Cancel editing
			if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   				$params = $environment->getCurrentParameterArray();
   				redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
			}

			// Delete item
			elseif ( isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
   				if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_deleted_ids')) {
      				$selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids');
                }
   				$manager = $environment->getManager($rubric);
   				foreach ($selected_ids as $id) {
      				$item = $manager->getItem($id);
      				$item->delete();
   				}
   				$session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_deleted_ids');
   				$params = $environment->getCurrentParameterArray();
   				unset($params['mode']);
   				unset($params['select']);
   				$selected_ids = array();
   				redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
			}

   			if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        			and !isset($_GET['show_copies'])
        			and $_POST['index_view_action'] != '-1'
        			and !empty($selected_ids)
      		) {
      		// prepare action process
      		switch ($_POST['index_view_action']) {
         		case 1:
            		$action = 'ENTRY_MARK_AS_READ';
            		$error = false;
            		$rubric_manager = $environment->getManager($rubric);
            		$noticed_manager = $environment->getNoticedManager();
            		foreach ($selected_ids as $id) {
               			$item = $rubric_manager->getItem($id);
               			$version_id = $item->getVersionID();
               			$noticed_manager->markNoticed($id, $version_id );
               			$annotation_list =$item->getAnnotationList();
               			if ( !empty($annotation_list) ){
                  			$annotation_item = $annotation_list->getFirst();
                  			while($annotation_item){
                     			$noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                     			$annotation_item = $annotation_list->getNext();
                  			}
               			}
            		}
            		break;
         		case 2:
            		$action = 'ENTRY_COPY';
            		// Copy to clipboard
            		foreach ($selected_ids as $id) {
               			if ( !in_array($id, $this->_list_parameter_arrray['clipboard_id_array']) ) {
                  			$this->_list_parameter_arrray['clipboard_id_array'][] = $id;
               			}
            		}
            		break;
         		case 3:
            		$user = $environment->getCurrentUserItem();
            		if( $user->isModerator() ){
                		$session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               			$params = $environment->getCurrentParameterArray();
               			$params['mode'] = 'list_actions';
               			$page->addDeleteBox(curl($environment->getCurrentContextID(),$rubric,'index',$params),'index',$selected_ids);
               			unset($params);
            		}
            		break;
         		case 'download':
            		include_once('include/inc_rubric_download.php');
            		break;
         		default:
            		if ( !empty($_POST['index_view_action'])
                 			and ( $environment->isPlugin($_POST['index_view_action'])
                       		or $environment->isPlugin(substr($_POST['index_view_action'],0,strpos($_POST['index_view_action'],'_')))
                     	)) {
               			$plugin = '';
               			if ( $environment->isPlugin($_POST['index_view_action']) ) {
                  			$plugin = $_POST['index_view_action'];
               			} else {
                  			$plugin = substr($_POST['index_view_action'],0,strpos($_POST['index_view_action'],'_'));
               			}
               			plugin_hook_plugin($plugin,'performListAction',$_POST);
            		} else {
               			$params = $environment->getCurrentParameterArray();
               			unset($params['mode']);
               			redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
            		}
      			}
      			if ($_POST['index_view_action'] != '3'){
         			$selected_ids = array();
         			$session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      			}
      		}
		}

		protected function initListParameters($rubric){
			$environment = $this->_environment;
			$session = $environment->getSessionItem();
			if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   				$index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   				$params['interval'] = $index_search_parameter_array['interval'];
   				$params['sort'] = $index_search_parameter_array['sort'];
				$params['selbuzzword'] = $index_search_parameter_array['selbuzzword'];
   				$params['seltag_array'] = $index_search_parameter_array['seltag_array'];
   				$params['interval'] = $index_search_parameter_array['interval'];
   				$params['sel_activating_status'] = $index_search_parameter_array['sel_activating_status'];
   				$sel_array = $index_search_parameter_array['sel_array'];
   				foreach($sel_array as $key => $value){
      				$params['sel'.$key] = $value;
   				}
   				$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   				$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   				redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
			}
			if ( isset($_GET['ref_iid']) ) {
   				$this->_list_parameter_arrray['ref_iid'] = $_GET['ref_iid'];
			} elseif ( isset($_POST['ref_iid']) ) {
   				$this->_list_parameter_arrray['ref_iid'] = $_POST['ref_iid'];
			}

			if ( isset($_GET['ref_user']) ) {
   				$this->_list_parameter_arrray['ref_user'] = $_GET['ref_user'];
			} elseif ( isset($_POST['ref_user']) ) {
   				$this->_list_parameter_arrray['ref_user'] = $_POST['ref_user'];
			}else{
   				$this->_list_parameter_arrray['ref_user'] ='';
			}

			// Find clipboard id array
			if ( $session->issetValue('announcement_clipboard') ) {
   				$this->_list_parameter_arrray['clipboard_id_array']= $session->getValue('announcement_clipboard');
			} else {
   				$this->_list_parameter_arrray['clipboard_id_array'] = array();
			}


			// Handle attaching
			if ( $this->getViewMode() == 'formattach' or $this->getViewMode() == 'detailattach' ) {
   				$attach_type = $rubric;
   				include('pages/index_attach_inc.php');
			}

			// Find current browsing starting point
			if ( isset($_GET['from']) ) {
   				$this->_list_parameter_arrray['from'] = $_GET['from'];
			}  else {
   				$this->_list_parameter_arrray['from'] = 1;
			}

			// Find current browsing interval
			// The browsing interval is applied to all rubrics
			$context_item = $environment->getCurrentContextItem();
			if ( isset($_GET['interval']) ) {
   				$this->_list_parameter_arrray['interval'] = $_GET['interval'];
			} elseif ( $session->issetValue('interval') ) {
   				$this->_list_parameter_arrray['interval'] = $session->getValue('interval');
			} else{
   				$this->_list_parameter_arrray['interval'] = $context_item->getListLength();
			}

			if ( isset($_GET['sort']) ) {
   				$this->_list_parameter_arrray['sort'] = $_GET['sort'];
			}  else {
   				$this->_list_parameter_arrray['sort'] = 'modified';
			}

			if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   				$this->_list_parameter_arrray['search'] = '';
   				$this->_list_parameter_arrray['selinstitution'] = '';
   				$this->_list_parameter_arrray['seltopic'] = '';
   				$this->_list_parameter_arrray['last_selected_tag'] = '';
   				$this->_list_parameter_arrray['$seltag_array'] = array();
   				$this->_list_parameter_arrray['sel_activating_status'] = '';
			} else {
   				$this->_list_parameter_arrray['sel_activating_status'] = '';

   				// Find current search text
   				if ( isset($_GET['search']) and ($_GET['search'] != $translator->getMessage('COMMON_SEARCH_IN_ROOM') || $_GET['search'] != $translator->getMessage('COMMON_SEARCH_IN_RUBRIC'))) {
      				$this->_list_parameter_arrray['search'] = $_GET['search'];
   				}  else {
      				$this->_list_parameter_arrray['search'] = '';
   				}

   				// Find current sel_activating_status selection
   				if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
      				$this->_list_parameter_arrray['sel_activating_status'] = $_GET['selactivatingstatus'];
   				} else {
      				$this->_list_parameter_arrray['sel_activating_status'] = 2;
   				}

				// Find current buzzword selection
   				if ( isset($_GET['selbuzzword']) and $_GET['selbuzzword'] !='-2') {
      				$this->_list_parameter_arrray['selbuzzword'] = $_GET['selbuzzword'];
   				} else {
      				$this->_list_parameter_arrray['selbuzzword'] = 0;
   				}

   				// Find current tag selection
    			$last_selected_tag = '';
				if ( isset($_GET['seltag']) and $_GET['seltag'] =='yes') {
      				$i = 0;
      				while ( !isset($_GET['seltag_'.$i]) ){
         				$i++;
      				}
      				$seltag_array[] = $_GET['seltag_'.$i];
      				$j = 0;
      				while(isset($_GET['seltag_'.$i]) and $_GET['seltag_'.$i] !='-2'){
         				if (!empty($_GET['seltag_'.$i])){
            				$seltag_array[$i] = $_GET['seltag_'.$i];
            				$j++;
         				}
         				$i++;
      				}
      				$this->_list_parameter_arrray['last_selected_tag'] = $seltag_array[$j-1];
   				}else{
      				$this->_list_parameter_arrray['last_selected_tag'] = '';
      				$this->_list_parameter_arrray['seltag_array'] = array();
   				}

	   			$context_item = $environment->getCurrentContextItem();
   				$current_room_modules = $context_item->getHomeConf();
   				if ( !empty($current_room_modules) ){
      				$room_modules = explode(',',$current_room_modules);
   				} else {
      				$room_modules =  $default_room_modules;
   				}
				$sel_array = array();
   				foreach ( $room_modules as $module ) {
      				$link_name = explode('_', $module);
      				if ( $link_name[1] != 'none' ) {
         				if ($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) {
            				// Find current institution selection
            				$string = 'sel'.$link_name[0];
            				if ( isset($_GET[$string]) and $_GET[$string] !='-2') {
               					$sel_array[$link_name[0]] = $_GET[$string];
	            			} else {
    	           				$sel_array[$link_name[0]] = 0;
        	    			}
         				}
      				}
   				}
   				$this->_list_parameter_arrray['sel_array'] = $sel_array;
			}
		}

		protected function getAvailableBuzzwords(){
			// Get available buzzwords
			$buzzword_manager = $environment->getLabelManager();
			$buzzword_manager->resetLimits();
			$buzzword_manager->setContextLimit($environment->getCurrentContextID());
			$buzzword_manager->setTypeLimit('buzzword');
			$buzzword_manager->setGetCountLinks();
			$buzzword_manager->select();
			$buzzword_list = $buzzword_manager->get();
			return $buzzword_list;
		}



		/**
		 * returns an array, containing the requested list information
		 * @param $rubric_array - item types
		 * @param $limit - optional limit per rubric
		 */
		protected function getListContent($rubric_array, $limit = null) {
			$list = new cs_list();
			$return = array();

			// check limit
			if($limit == null) $limit = $this->_entries_per_page;

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

					case CS_DISCUSSION_TYPE:
						$manager = $this->_environment->getDiscussionManager();
						$manager->reset();
						$manager->setContextLimit($this->_environment->getCurrentContextID());
						$count_all = $manager->getCountAll();
						/*
						if ($environment->inProjectRoom() or $environment->inGroupRoom() ) {
							$manager->setAgeLimit($context_item->getTimeSpread());
						} elseif ($environment->inCommunityRoom()) {
							$manager->setIntervalLimit(0,5);
							$home_rubric_limit = 5;
						}
						*/
						$manager->showNoNotActivatedEntries();

						$manager->setIntervalLimit(0, $limit);
						/*
						$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

						if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);
						*/

						$manager->select();
						$list = $manager->get();
						/*
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
						*/
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