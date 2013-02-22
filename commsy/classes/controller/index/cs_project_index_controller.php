<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_project_index_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'project_list';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_PROJECT_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		public function actionIndex() {
			// init list params
			$this->initListParameters(CS_PROJECT_TYPE);

			// perform list options
			$this->performListOption(CS_PROJECT_TYPE);

			// get list content
			$list_content = $this->getListContent();

			// assign to template
			$this->assign('project','list_parameters', $this->_list_parameter_arrray);
			$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
			$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
			$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
			$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
			$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
			$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
			$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
			$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
			$this->assign('project','list_content', $list_content);
		}

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$current_user = $environment->getCurrentUserItem();
			$translator = $environment->getTranslationObject();
			$return = array();

			// Get data from database
			$project_manager = $environment->getProjectManager();
			$project_manager->reset();
			if ($environment->inCommunityRoom()) {
			   $project_manager->setContextLimit($environment->getCurrentPortalID());
			   if ( !isset($c_cache_cr_pr) or $c_cache_cr_pr ) {
			      $project_manager->setCommunityroomLimit($environment->getCurrentContextID());
			   } else {
			      /**
			       * use redundant infos in community room
			       */
			      $project_manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
			   }
			} else {
			   $project_manager->setContextLimit($environment->getCurrentContextID());
			}
			$count_all = $project_manager->getCountAll();

			if ( !empty($this->_list_parameter_arrray['sort']) ) {
   				$project_manager->setOrder($this->_list_parameter_arrray['sort']);
			}
			if ( !empty($this->_list_parameter_arrray['search']) ) {
   				$project_manager->setSearchLimit($this->_list_parameter_arrray['search']);
			}
			if ( $this->_list_parameter_arrray['interval'] > 0 ) {
   				$project_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
			}
			$project_manager->select();
			$list = $project_manager->get();
			$ids = $project_manager->getIDArray();
			$count_all_shown = count($ids);
			$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
            $this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

            $session = $this->_environment->getSessionItem();
            $session->setValue('cid'.$environment->getCurrentContextID().'_project_index_ids', $ids);

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
			$project_manager = $environment->getProjectManager();
			if ($this->_environment->inCommunityRoom()) {
			   $project_manager->setContextLimit($environment->getCurrentPortalID());
			}
	        $room_max_activity = 0;
	        global $c_cache_cr_pr;
	        if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
	           $room_max_activity = $project_manager->getMaxActivityPointsInCommunityRoom($environment->getCurrentContextID());
	        } else {
	           $current_context_item = $environment->getCurrentContextItem();
	           $room_max_activity = $project_manager->getMaxActivityPointsInCommunityRoomInternal($current_context_item->getInternalProjectIDArray());
	           unset($current_context_item);
	        }
			$item = $list->getFirst();
			$item_array = array();
			$params = array();
			$params['environment'] = $environment;
			$params['with_modifying_actions'] = false;
			$view = new cs_view($params);
			$user_manager = $this->_environment->getUserManager();
			while($item) {

			    $user_manager->setUserIDLimit($current_user->getUserID());
			    $user_manager->setAuthSourceLimit($current_user->getAuthSource());
			    $user_manager->setContextLimit($item->getItemID());
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
		           $may_enter = $item->mayEnter($room_user);
		        } else {
		           $may_enter = false;
		        }


				$noticed_text = $this->_getItemChangeStatus($item);
				$contact_list = $item->getContactModeratorList();
				$contact_item = $contact_list->getFirst();
				$contact_array = array();
				while($contact_item){
					$contact_array[] = $contact_item->getFullName();
					$contact_item = $contact_list->getNext();
				}
				$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $item->getTitle(),
					'noticed'			=> $noticed_text,
					'modificator'		=> $this->getItemModificator($item),
					'contacts'			=> $contact_array,
					'members_count'		=> $item->getAllUsers(),
					'may_enter'			=> $may_enter,
					'activity'			=> $this->_getItemActivity ($item,$room_max_activity)
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



		   function _getItemActivity ($item,$room_max_activity) {
		      if ( $room_max_activity != 0 ) {
		         $percentage = $item->getActivityPoints();
		         if ( empty($percentage) ) {
		            $percentage = 0;
		         } else {
		           $teiler = $room_max_activity/20;
		            $percentage = log(($percentage/$teiler)+1);
		          if ($percentage < 0) {
		            $percentage = 0;
		          }
		          $max_activity = log(($room_max_activity/$teiler)+1);
		            $percentage = round(($percentage / $max_activity) * 100,2);
		         }
		      } else {
		         $percentage = 0;
		      }
		      $display_percentage = $percentage;
		      $html = '         <div class="gauge" style="height:5px;">'.LF;
		      $html .= '            <div class="gauge-bar" style="height:5px; width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
		      $html .= '         </div>'.LF;
		      return $html;
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