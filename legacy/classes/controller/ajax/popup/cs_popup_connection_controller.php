<?php
	require_once('classes/controller/ajax/popup/cs_popup_controller.php');
	
	class cs_popup_connection_controller implements cs_popup_controller {
		private $_environment = null;
		private $_translator = null;
		private $_popup_controller = null;
		private $_toggle_archive_mode = false;
		private $_tab_id = '';
		private $_tab_id_first = '';
		
		/**
		* constructor
		*/
		public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller=null) {
			$this->_environment = $environment;
			$this->_popup_controller = $popup_controller;
		}
		
		public function setTabID ( $value ) {
			if ( !empty($value) )  {
			   $this->_tab_id = $value;
			}
		}
		
		public function save ( $form_data, $additional = array() ) {
			#pr($form_data);
			#pr($additional);
			#exit;
			if ( !empty($additional)
				  and !empty($additional['action'])
				  and $additional['action'] == 'saveNew'
				) {
				$this->saveNew($form_data,$additional);
			} else {
				$this->saveCurrent($form_data,$additional);
			}
		}
		
		public function saveNew ($form_data, $additional = array()) {
			$translator = $this->_environment->getTranslationObject();
			if ( empty($form_data['new_userid'])
				  or empty($form_data['new_pwd'])
				  or empty($form_data['new_portal'])
				  or !stristr($form_data['new_portal'],'_')
			   ) {
				$this->_popup_controller->setErrorReturn("1001",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_1'));
			} else {
				
				// data conversion
				$userid = $form_data['new_userid'];
				$password = $form_data['new_pwd'];
				$new_portal_array = explode('_', $form_data['new_portal']);
				$portal_id = $new_portal_array[1];
				$server_id = $new_portal_array[0];
				
				// save new connection
				$commsy_connection_obj = $this->_environment->getCommSyConnectionObject();
				$result = $commsy_connection_obj->saveNewConnection($server_id,$portal_id,$userid,$password);
				if ( $result == 'LOGIN_FAILED' ) {
					$this->_popup_controller->setErrorReturn("1002",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_2'));
				} elseif ( $result == 'SAVE_FAILED' ) {
				   $this->_popup_controller->setErrorReturn("1003",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_3'));
				} elseif ( $result == 'SAVE_KEY_FAILED' ) {
				   $this->_popup_controller->setErrorReturn("1003",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_3'));
				} elseif ( $result == 'SAVE_TAB_FAILED' ) {
					$this->_popup_controller->setErrorReturn("1003",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_3'));
				} elseif ( $result == 'DATA_LOST' ) {
				   $this->_popup_controller->setErrorReturn("1003",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_3'));
				} else {
					$data = array();
					$data['action'] = 'new';
					$data['id'] = $result['id'];
	            $server_item = $this->_environment->getServerItem();
	            $server_info = $server_item->getServerConnectionInfo($result['server_connection_id']);
					$data['server_name'] = $server_info['title'];
					$data['portal_name'] = $result['title'];
					$data['message_delete'] = $translator->getMessage('COMMON_DELETE_BUTTON');
					$this->_popup_controller->setSuccessfullDataReturn($data);
				}				
			}
		} 
	
		public function saveCurrent($form_data, $additional = array()) {
			$translator = $this->_environment->getTranslationObject();
				
			// data conversion
			$tabid_array = array();
			$sort_array = array();
			$sort = false;
			foreach ( $form_data as $key => $value ) {
				if ( substr($key,0,6) == 'tabid_') {
					$tabid_array[] = $value;
				}
				if ( substr($key,0,5) == 'sort_') {
					$sort_array[] = substr($key,5);
					if ( count($sort_array)-1 !=  end($sort_array) ) {
						$sort = true;
					}
				}
			}
			
			if ( !empty($tabid_array) ) {
				
				// get portal user
				$user_item = $this->_environment->getCurrentUserItem();
				if ( !$this->_environment->inPortal() ) {
					$user_item = $user_item->getRelatedCommSyUserItem();
				}
				
				$portal_conn_array = $user_item->getPortalConnectionArrayDB();
				
				// sort
				if ( $sort ) {
					$sort_array = array_flip($sort_array);
					$new_portal_conn_array = array();
               foreach ( $portal_conn_array as $key => $conn ) {
               	$new_portal_conn_array[$sort_array[$key]] = $conn;
               }
				   $portal_conn_array = $new_portal_conn_array;
               ksort($portal_conn_array);
				}
				
				// handle data
				$delete = false;
	         foreach ( $tabid_array as $tab_id ) {
	         	
	         	if ( stristr($tab_id,'new') ) {
	         		$new_tab = array();
	         		$new_tab['id'] = md5($tab_id.rand(0,100).date(YmdHis).rand(0,100).$form_data['name_'.$tab_id]);
	         		$new_tab['server_connection_id'] = $form_data['server_connection_id_'.$tab_id];
	         		$new_tab['portal_connection_id'] = $form_data['portal_connection_id_'.$tab_id];
	         		$new_tab['title'] = $form_data['name_'.$tab_id];
	         		$new_tab['title_original'] = $form_data['name_orig_'.$tab_id];
	         	} else {
	         	
	         		foreach ( $portal_conn_array as $key =>  $connection ) {
		         		if ( $connection['id'] == $tab_id ) {
		         			
		         			if ( !empty($form_data['delete_'.$tab_id]) ) {
		         				// delete tab external
		         				$connection_obj = $this->_environment->getCommSyConnectionObject();
		         				if ( !empty($connection_obj) ) {
		         					$result2 = $connection_obj->deleteConnection($portal_conn_array[$key]['id']);
		         				}
		         				
		         				// delete tab local
		         				unset($portal_conn_array[$key]);
		         				$delete = true;
		         			} else {
		         			   if ( !empty($form_data['name_'.$tab_id]) ) {
		         				   $portal_conn_array[$key]['title'] = $form_data['name_'.$tab_id];
		         			   }
		         			}
		         			break;
		         		}
		         	}
	         	}
	         }
	         
	         if ( $delete ) {
	         	$new_portal_conn_array = array();
	         	foreach ( $portal_conn_array as $conn ) {
	         		$new_portal_conn_array[] = $conn;
	         	}
	         	$portal_conn_array = $new_portal_conn_array;
	         }
	         
	         $user_item->setPortalConnectionInfoDB($portal_conn_array);
	         $user_item->save();	         
			   
	         // set return
			   $this->_popup_controller->setSuccessfullItemIDReturn($user_item->getItemID());
			} else {
			   // set return
				$this->_popup_controller->setErrorReturn("1011",$translator->getMessage('CS_BAR_CONNECTION_JS_ERROR_3'));
			}
		}
	
		public function initPopup($data) {
			$this->_popup_controller->assign('popup', 'tabs', $this->getTabInformation());
			
			if ( !empty($this->_tab_id) and $this->_tab_id != 'tabs_edit_new' ) {
				$this->_popup_controller->assign('popup', 'with_tabs', -1);
			   $this->_popup_controller->assign('popup', 'rooms', $this->_getExternalRoomListArray($this->_tab_id));
			} elseif ( !empty($this->_tab_id_first) ) {
				$this->_popup_controller->assign('popup', 'with_tabs', 1);
			   $this->_popup_controller->assign('popup', 'rooms', $this->_getExternalRoomListArray($this->_tab_id_first));
			} else {
				// only edit tab
				$this->_popup_controller->assign('popup', 'with_tabs', 1);
			   $this->_popup_controller->assign('popup', 'server', $this->_getServerAndPortalInfoArray());			
			}
			
			// edit infos
			if ( !empty($this->_tab_id) and $this->_tab_id == 'tabs_edit_new' ) {
				$this->_popup_controller->assign('popup', 'only_edit', 1);
			   $this->_popup_controller->assign('popup', 'server', $this->_getServerAndPortalInfoArray());
			} else {
				$this->_popup_controller->assign('popup', 'only_edit', 0);
			}
		}
		
		private function _getServerAndPortalInfoArray () {
			$retour = array();
			
			$server_item = $this->_environment->getServerItem();
			$server_conn_array = $server_item->getServerConnectionArray();
			
			foreach ( $server_conn_array as $server_info ) {
			   
			   // get portals from server
			   $connection_obj = $this->_environment->getCommSyConnectionObject();
      	   if ( !empty($connection_obj) ) {
      	      $portal_array = $connection_obj->getPortalArrayFromServer($server_info['id']);
      	      foreach ( $portal_array as $portal_info ) {
      	      	$portal_info['server_id'] = $server_info['id'];
      	      	$retour[$server_info['title']][] = $portal_info;
      	      }
      	   }
			}
			
			return $retour;
		}
	
		private function getTabInformation() {
			$return = array();
	
			// get tab infos from portal user
			$current_user = $this->_environment->getCurrentUserItem();
			if ( !$this->_environment->inPortal() ) {
				$portal_user = $current_user->getRelatedCommSyUserItem();
			} else {
				$portal_user = $current_user;
			}
			$portal_connection_array = $portal_user->getPortalConnectionArray();
			if ( !empty($portal_connection_array) ) {
				$first = true;
				foreach ( $portal_connection_array as $portal_connection_info ) {
					if ( $first ) {
						$first = false;
						$this->_tab_id_first = $portal_connection_info['id'];
					}
					$return[] = array(
							'id'	=> $portal_connection_info['id'],
							'title'	=> $portal_connection_info['title'],
							'title_orig'	=> $portal_connection_info['title_original'],
							'server_name' => $portal_connection_info['server_info']['title']
					);
				}
			}
			return $return;
		}
	
		private function cleanup_session($current_iid) {
			$environment = $this->_environment;
			$session = $this->_environment->getSessionItem();
	
			$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
			$session->unsetValue($environment->getCurrentModule().'_add_tags');
			$session->unsetValue($environment->getCurrentModule().'_add_files');
			$session->unsetValue($current_iid.'_post_vars');
		}
	
	   function _getCustomizedRoomListForCurrentUser(){
	      $retour = array();
	      $current_user = $this->_environment->getCurrentUserItem();
	      $current_context_id = $this->_environment->getCurrentContextID();
	      $own_room_item = $current_user->getOwnRoom();
	      $customized_room_list = $own_room_item->getCustomizedRoomListCommSy8();
	      
	      if ( isset($customized_room_list) ) {
	         $room_item = $customized_room_list->getFirst();
	         while ($room_item) {
	            $temp_array = array();
	            if ( $room_item->isGrouproom() ) {
	               $temp_array['title'] = '- '.$room_item->getTitle();
	            } else {
	               $temp_array['title'] = $room_item->getTitle();
	            }
	            if ( mb_strlen($temp_array['title']) > 28 ) {
	               $temp_array['title'] = mb_substr($temp_array['title'],0,28);
	               $temp_array['title'] .= '...';
	            }
	            $temp_array['item_id'] = $room_item->getItemID();
	            if ($current_context_id == $temp_array['item_id']){
	               $temp_array['selected'] = true;
	            }
	            $retour[] = $temp_array;
	            $room_item = $customized_room_list->getNext();
	         }
	      }
	      
	      return $retour;
	   }
	
	
	   function _getAllOpenContextsForCurrentUser () {
	   	$this->_translator = $this->_environment->getTranslationObject();
	   	$current_user = $this->_environment->getCurrentUserItem();
	   	$own_room_item = $current_user->getOwnRoom();
	   	if ( isset($own_room_item) ) {
	   		$customized_room_array = $own_room_item->getCustomizedRoomIDArray();
	   	}
	   	if (isset($customized_room_array[0])){
	   		return $this->_getCustomizedRoomListForCurrentUser();
	   	}else{
	   		$this->translatorChangeToPortal();
	   		$selected = false;
	   		$selected_future = 0;
	   		$selected_future_pos = -1;
	   		$retour = array();
	   		$temp_array = array();
	   		$temp_array['item_id'] = -1;
	   		$temp_array['title'] = '';
	   		$retour[] = $temp_array;
	   		unset($temp_array);
	   		$temp_array = array();
	   		$community_list = $current_user->getRelatedCommunityList();
	   		if ( $community_list->isNotEmpty() ) {
	   			$temp_array['item_id'] = -1;
	   			$temp_array['title'] = $this->_translator->getMessage('MYAREA_COMMUNITY_INDEX');
	     			if ( $this->_environment->isArchiveMode() ) {
	   			   $temp_array['title'] .= ' ('.$this->_translator->getMessage('COMMON_CLOSED').')';
	   			}
	   			$retour[] = $temp_array;
	   			unset($temp_array);
	   			$community_item = $community_list->getFirst();
	   			while ($community_item) {
	   				$temp_array = array();
	   				$temp_array['item_id'] = $community_item->getItemID();
	   				$title = $community_item->getTitle();
	   				$temp_array['title'] = $title;
	   				if ( $community_item->getItemID() == $this->_environment->getCurrentContextID()
	   					  and !$selected
	   				   ) {
	   					$temp_array['selected'] = true;
	   					$selected = true;
	   				}
	
	   				$retour[] = $temp_array;
	   				unset($temp_array);
	   				unset($community_item);
	   				$community_item = $community_list->getNext();
	   			}
	   			$temp_array = array();
	   			$temp_array['item_id'] = -1;
	   			$temp_array['title'] = '';
	   			$retour[] = $temp_array;
	   			unset($community_list);
	   		}
	   		$portal_item = $this->_environment->getCurrentPortalItem();
	   		if ($portal_item->showTime()) {
	   			$project_list = $current_user->getRelatedProjectListSortByTimeForMyArea();
	   			#         if ( $portal_item->showGrouproomConfig() ) {
	   			include_once('classes/cs_list.php');
	   			$new_project_list = new cs_list();
	   			$grouproom_array = array();
	   			$project_grouproom_array = array();
	   			if ( $project_list->isNotEmpty() ) {
	   				$room_item = $project_list->getFirst();
	   				while ($room_item) {
	   					if ( $room_item->isA(CS_GROUPROOM_TYPE) ) {
	   						$grouproom_array[$room_item->getItemID()] = $room_item->getTitle();
	   						$linked_project_item_id = $room_item->getLinkedProjectItemID();
	   						$project_grouproom_array[$linked_project_item_id][] = $room_item->getItemID();
	   					} else {
	   						$new_project_list->add($room_item);
	   					}
	   					unset($room_item);
	   					$room_item = $project_list->getNext();
	   				}
	   				unset($project_list);
	   				$project_list = $new_project_list;
	   				unset($new_project_list);
	   			}
	   			#         }
	   			$future = true;
	   			$future_array = array();
	   			$no_time = false;
	   			$no_time_array = array();
	   			$current_time = $portal_item->getTitleOfCurrentTime();
	   			$with_title = false;
	   		} else {
	   			$project_list = $current_user->getRelatedProjectListForMyArea();
	   			#         if ( $portal_item->showGrouproomConfig() ) {
	   			include_once('classes/cs_list.php');
	   			$new_project_list = new cs_list();
	   			$grouproom_array = array();
	   			$project_grouproom_array = array();
	   			if ( $project_list->isNotEmpty() ) {
	   				$room_item = $project_list->getFirst();
	   				while ($room_item) {
	   					if ( $room_item->isA(CS_GROUPROOM_TYPE) ) {
	   						$grouproom_array[$room_item->getItemID()] = $room_item->getTitle();
	   						$linked_project_item_id = $room_item->getLinkedProjectItemID();
	   						$project_grouproom_array[$linked_project_item_id][] = $room_item->getItemID();
	   					} else {
	   						$new_project_list->add($room_item);
	   					}
	   					unset($room_item);
	   					$room_item = $project_list->getNext();
	   				}
	   				unset($project_list);
	   				$project_list = $new_project_list;
	   				unset($new_project_list);
	   			}
	   			#         }
	   		}
	   		unset($current_user);
	   		if ( $project_list->isNotEmpty() ) {
	   			$temp_array['item_id'] = -1;
	   			$temp_array['title'] = $this->_translator->getMessage('MYAREA_PROJECT_INDEX');
	   			if ( $this->_environment->isArchiveMode() ) {
	   			   $temp_array['title'] .= ' ('.$this->_translator->getMessage('COMMON_CLOSED').')';
	   			}
	   			$retour[] = $temp_array;
	   			unset($temp_array);
	   			$project_item = $project_list->getFirst();
	   			while ($project_item) {
	   				$temp_array = array();
	   				if ( $project_item->isA(CS_PROJECT_TYPE)
	   				) {
	   					$temp_array['item_id'] = $project_item->getItemID();
	   					$title = $project_item->getTitle();
	   					$temp_array['title'] = $title;
	   					if ( $project_item->getItemID() == $this->_environment->getCurrentContextID()
	   							and ( !$selected
	   									or $selected_future == $project_item->getItemID()
	   							)
	   					) {
	   						$temp_array['selected'] = true;
	   						if ( !empty($selected_future)
	   								and $selected_future != 0
	   								and $selected_future_pos != -1
	   						) {
	   							$selected_future = 0;
	   							unset($future_array[$selected_future_pos]['selected']);
	   						}
	   						$selected = true;
	   					}
	
	   					// grouprooms
	   					#               if ( $portal_item->showGrouproomConfig() ) {
	   					if ( isset($project_grouproom_array[$project_item->getItemID()]) and !empty($project_grouproom_array[$project_item->getItemID()]) and $project_item->isGrouproomActive()) {
	   						$group_result_array = array();
	   						$project_grouproom_array[$project_item->getItemID()]= array_unique($project_grouproom_array[$project_item->getItemID()]);
	   						foreach ($project_grouproom_array[$project_item->getItemID()] as $value) {
	   							$group_temp_array = array();
	   							$group_temp_array['item_id'] = $value;
	   							$group_temp_array['title'] = '- '.$grouproom_array[$value];
	   							if ( $value == $this->_environment->getCurrentContextID()
	   									and ( !$selected
	   											or $selected_future == $value
	   									)
	   							) {
	   								$group_temp_array['selected'] = true;
	   								$selected = true;
	   								if ( !empty($selected_future)
	   										and $selected_future != 0
	   										and $selected_future_pos != -1
	   								) {
	   									$selected_future = 0;
	   									unset($future_array[$selected_future_pos]['selected']);
	   								}
	   							}
	   							$group_result_array[] = $group_temp_array;
	   							unset($group_temp_array);
	   						}
	   					}
	   					#               }
	   				} else {
	   					$with_title = true;
	   					$temp_array['item_id'] = -2;
	   					$title = $project_item->getTitle();
	   					if (!empty($title) and $title != 'COMMON_NOT_LINKED') {
	   						$temp_array['title'] = $this->_translator->getTimeMessage($title);
	   					} else {
	   						$temp_array['title'] = $this->_translator->getMessage('COMMON_NOT_LINKED');
	   						$no_time = true;
	   					}
	   					if (!empty($title) and $title == $current_time) {
	   						// if (!empty($title) and !empty($current_time) and $title == $current_time) {
	   						$future = false;
	   					}
	   				}
	   				if ($portal_item->showTime()) {
	   					if ($no_time) {
	   						$no_time_array[] = $temp_array;
	   						if ( isset($group_result_array) and !empty($group_result_array) ) {
	   							$no_time_array = array_merge($no_time_array,$group_result_array);
	   							unset($group_result_array);
	   						}
	   					} elseif ($future) {
	   						if ($temp_array['item_id'] != -2) {
	   							$future_array[] = $temp_array;
	   							if ( !empty($temp_array['selected']) and $temp_array['selected'] ) {
	   								$selected_future = $temp_array['item_id'];
	   								$selected_future_pos = count($future_array)-1;
	   							}
	   							if ( isset($group_result_array) and !empty($group_result_array) ) {
	   								$future_array = array_merge($future_array,$group_result_array);
	   								unset($group_result_array);
	   							}
	   						}
	   					} else {
	   						$retour[] = $temp_array;
	   						if ( isset($group_result_array) and !empty($group_result_array) ) {
	   							$retour = array_merge($retour,$group_result_array);
	   							unset($group_result_array);
	   						}
	   					}
	   				} else {
	   					$retour[] = $temp_array;
	   					if ( isset($group_result_array) and !empty($group_result_array) ) {
	   						$retour = array_merge($retour,$group_result_array);
	   						unset($group_result_array);
	   					}
	   				}
	   				unset($temp_array);
	   				unset($project_item);
	   				$project_item = $project_list->getNext();
	   			}
	   			unset($project_list);
	   			if ($portal_item->showTime()) {
	
	   				// special case, if no room is linked to a time pulse
	   				if (isset($with_title) and !$with_title) {
	   					$temp_array = array();
	   					$temp_array['item_id'] = -2;
	   					$temp_array['title'] = $this->_translator->getMessage('COMMON_NOT_LINKED');
	   					$retour[] = $temp_array;
	   					unset($temp_array);
	   					$retour = array_merge($retour,$future_array);
	   					$future_array = array();
	   				}
	
	   				if (!empty($future_array)) {
	   					$future_array2 = array();
	   					$future_array3 = array();
	   					foreach ($future_array as $element) {
	   						if ( !in_array($element['item_id'],$future_array3) ) {
	   							$future_array3[] = $element['item_id'];
	   							$future_array2[] = $element;
	   						}
	   					}
	   					$future_array = $future_array2;
	   					unset($future_array2);
	   					unset($future_array3);
	   					$temp_array = array();
	   					$temp_array['title'] = $this->_translator->getMessage('COMMON_IN_FUTURE');
	   					$temp_array['item_id'] = -2;
	   					$future_array_begin = array();
	   					$future_array_begin[] = $temp_array;
	   					$future_array = array_merge($future_array_begin,$future_array);
	   					unset($temp_array);
	   					$retour = array_merge($retour,$future_array);
	   				}
	
	   				if (!empty($no_time_array)) {
	   					$retour = array_merge($retour,$no_time_array);
	   				}
	   			}
	   		}
	   		unset($portal_item);
	   		$this->translatorChangeToCurrentContext();
	   		
	   		// archive - BEGIN
	   		if ( !$this->_toggle_archive_mode ) {
	   		   $this->_toggle_archive_mode = true;
   	   		if ( $this->_environment->isArchiveMode() ) {
   	   		   $this->_environment->deactivateArchiveMode();
   	   		   $retour2 = $this->_getAllOpenContextsForCurrentUser();
   	   		   if ( !empty($retour2) ) {
   	   		      $retour = array_merge($retour2,$retour);
   	   		   }	   		   
   	   		   $this->_environment->activateArchiveMode();
   	   		} else {
   	   		   $this->_environment->activateArchiveMode();
   	   		   $retour2 = $this->_getAllOpenContextsForCurrentUser();
   	   		   if ( !empty($retour2) ) {
   	   		      $retour = array_merge($retour,$retour2);
   	   		   }
   	   		   $this->_environment->deactivateArchiveMode();
   	   		}
	   		}
	   		// archive - END
	   		
	   		return $retour;
	   	}
	   }
	
	   function translatorChangeToPortal () {
	     $current_portal = $this->_environment->getCurrentPortalItem();
	     if (isset($current_portal)) {
	       $this->_translator->setContext(CS_PORTAL_TYPE);
	       $this->_translator->setRubricTranslationArray($current_portal->getRubricTranslationArray());
	       $this->_translator->setEmailTextArray($current_portal->getEmailTextArray());
	     }
	   }
	
	   function translatorChangeToCurrentContext () {
	     $current_context = $this->_environment->getCurrentContextItem();
	     if (isset($current_context)) {
	         if ($current_context->isCommunityRoom()) {
	          $this->_translator->setContext(CS_COMMUNITY_TYPE);
	         } elseif ($current_context->isProjectRoom()) {
	          $this->_translator->setContext(CS_PROJECT_TYPE);
	         } elseif ($current_context->isPortal()) {
	          $this->_translator->setContext(CS_PORTAL_TYPE);
	       } else {
	          $this->_translator->setContext(CS_SERVER_TYPE);
	       }
	       $this->_translator->setRubricTranslationArray($current_context->getRubricTranslationArray());
	       $this->_translator->setEmailTextArray($current_context->getEmailTextArray());
	     }
	   }
	
      private function _getExternalRoomListArray ( $id ) {
      	$retour = array();
      	$connection_obj = $this->_environment->getCommSyConnectionObject();
      	if ( !empty($connection_obj) ) {
      	   $context_array = $connection_obj->getAllOpenContextsForCurrentUser($id);
      	   if ( !empty($context_array) ) {
      	   	$retour = $context_array;
      	   }
      	}
      	return $retour;
      }
	   
		function getRoomListArray() {
			$return = array();
			
			$context_array = $this->_getAllOpenContextsForCurrentUser();
			$current_portal = $this->_environment->getCurrentPortalItem();
			$context_manager = $this->_environment->getRoomManager();

			$this->_environment->toggleArchiveMode();
		    $context_manager2 = $this->_environment->getRoomManager();
		    $this->_environment->toggleArchiveMode();
         
			$room_array = array();
			
			// this holds last headline and subline
			$headline = '';
			$subline = '';
			
			$checked_room_id_array = array();
			
			foreach($context_array as $context) {
				$item_id = $context['item_id'];
				$title = $context['title'];
				$selected = isset($context['selected']) ? $context['selected'] : '';
				
				$room = array();
				$additional = '';
				
				// selected
				if(isset($selected) && !empty($selected)) {
					$additional = 'selected';
				}
				
				// empty or headline
				if($item_id == -1) {
					$additional = 'disabled';
					if(!empty($title) && $title !== '----------------------------') {
						// update headline
						$headline = $title;
					}
					
					continue;
				}
				
				// disabled
				if($item_id == -2) {
					$additional = 'disabled';
					
					if(!empty($title) && $title !== '----------------------------') {
						// update headline
						$subline = $title;
					}
					
					continue;
				}
				
				//if($item_id <= -3) continue;
				
				$room = array(
						'item_id'		=> $item_id,
						'additional'	=> $additional,
						'title'			=> $title
				);
				
				$checked_room_id_array[] = $item_id;
				
				$context_item = $context_manager->getItem($item_id);
				
				if ( !isset($context_item)
					  and isset($context_manager2)
					) {
					$context_item = $context_manager2->getItem($item_id);
				}
				
				if (is_object($context_item)){
					$room['color_array'] = $context_item->getColorArray();
					$room['activity_array'] = $context_item->getActiveAndAllMembersAsArray();
					$room['page_impressions'] = $context_item->getPageImpressions();
					$room['new_entries'] = $context_item->getNewEntries();
					$room['time_spread'] = $context_item->getTimeSpread();
				}
				
				$return[$headline][$subline]['rooms'][] = $room;
			}
			
			
			// get unchecked rooms
			$room_manager = $this->_environment->getRoomManager();
			$room_list = $room_manager->getRelatedRoomListForUser($this->_environment->getCurrentUserItem());
			$room_item = $room_list->getFirst();
			$unchecked_rooms = array();
			
			while($room_item) {
				// skip if this room is already checked
				if(!in_array($room_item->getItemID(), $checked_room_id_array)) {
					if(!$room_item->isPrivateRoom() && $room_item->isUser($this->_environment->getCurrentUserItem())) {
						$return['unchecked']['']['rooms'][] = array(
							'item_id'	=> $room_item->getItemID(),
							'title'		=> $room_item->getTitle()
						);
					}
				}
				
				$room_item = $room_list->getNext();
			}
			
			return $return;
		}
	}