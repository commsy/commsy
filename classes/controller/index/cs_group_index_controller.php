<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_group_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'group_list';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_GROUP_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_GROUP_TYPE);

			// perform list options
			$this->performListOption(CS_GROUP_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('group','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('group','list_content', $list_content);
		}

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$translator = $environment->getTranslationObject();
            $current_user = $environment->getCurrentUser();
			$return = array();

			$last_selected_tag = '';
			$seltag_array = array();

			// Find current topic selection
			if(isset($_GET['seltag']) && $_GET['seltag'] == 'yes') {
				$i = 0;
				while(!isset($_GET['seltag_' . $i])) {
					$i++;
				}
				$seltag_array[] = $_GET['seltag_' . $i];
				$j = 0;
				while(isset($_GET['seltag_' . $i]) && $_GET['seltag_' . $i] != '-2') {
					if(!empty($_GET['seltag_' . $i])) {
						$seltag_array[$i] = $_GET['seltag_' . $i];
						$j++;
					}
					$i++;
				}
				$last_selected_tag = $seltag_array[$j-1];
			}

			// Get data from database
			$group_manager = $environment->getGroupManager();
			$group_manager->reset();
			$group_manager->setContextLimit($environment->getCurrentContextID());
			$group_manager->setTypeLimit('group');
			$count_all = $group_manager->getCountAll();

			if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
   				$group_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
			}
			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$group_manager->setSortOrder($this->_list_parameter_arrray['sort']);
			}else{
				$group_manager->setOrder('name');
			}
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$group_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			if ( !empty($this->_list_parameter_arrray['seltopic']) ) {
   				$group_manager->setTopicLimit($this->_list_parameter_arrray['seltopic']);
			}

			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$group_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}
			$group_manager->select();
			$list = $group_manager->get();
			$ids = $group_manager->getIDArray();
			$count_all_shown = count($ids);

			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);

			$id_array = array();
			$item = $list->getFirst();
			while ($item){
   				$id_array[] = $item->getItemID();
   				$item = $list->getNext();
			}
			$noticed_manager = $environment->getNoticedManager();
			$noticed_manager->getLatestNoticedByIDArray($id_array);
			$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);



			// prepare item array
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			while($item) {

                $is_grouproom = false;
                if($item->isGroupRoomActivated()) {
                    $group_room_manager = $environment->getGroupRoomManager();
                    $grouproom_item = $group_room_manager->getItem($item->getGroupRoomItemID());
                    if ($grouproom_item != null) {
                       $is_grouproom = true;
                       $user_manager = $environment->getUserManager();
                       $user_manager->setUserIDLimit($current_user->getUserID());
                       $user_manager->setAuthSourceLimit($current_user->getAuthSource());
                       $user_manager->setContextLimit($grouproom_item->getItemID());
                       $user_manager->select();
                       $user_list = $user_manager->get();
                       if (!empty($user_list)){
                          $room_user = $user_list->getFirst();
                       } else {
                          $room_user = '';
                       }
                       if ($current_user->isRoot()) {
                          $may_enter = true;
                       } elseif ( !empty($room_user) ) {
                          $may_enter = $grouproom_item->mayEnter($room_user);
                       } else {
                          $may_enter = false;
                       }
                    }
                }
                

				$noticed_text = $this->_getItemChangeStatus($item);
				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $item->getTitle(),
					'noticed'			=> $noticed_text,
					'modificator'		=> $this->getItemModificator($item),
					'members_count'		=> $item->getMemberItemList()->getCount(),
					'linked_entries'	=> count($item->getAllLinkedItemIDArray()),
					'is_grouproom'		=> $is_grouproom,
					'grouproom_id'		=> $item->getGroupRoomItemID(),
					'may_enter'			=> $may_enter
				);

				$item = $list->getNext();
			}

			// append return
			$return = array(
				'items'		=> $item_array,
				'count_all'	=> $count_all_shown
			);
			return $return;
		}

		protected function getAdditionalActions(&$perms) {
		}

		protected function getAdditionalListActions() {
			return array();
		}

		protected function getAdditionalRestrictionText(){
			$return = array();

			return $return;
		}

		protected function getAdditionalRestrictions() {
			$return = array();

			return $return;
		}
	}