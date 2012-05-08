<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_netnavigation_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionPerformRequest() {
			$return = array();
			
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUser();
			
			// get request data
			$item_id = $this->_data['item_id'];
			$module = $this->_data['module'];
			$current_page = $this->_data['current_page'];
			$restrictions = $this->_data['restrictions'];
			
			// get item
			$item_manager = $this->_environment->getItemManager();
			$temp_item = $item_manager->getItem($item_id);
			
			if(isset($temp_item)) {
				$manager = $this->_environment->getManager($temp_item->getItemType());
				$item = $manager->getItem($item_id);
			}
			
			// get ids of linked items
			$selected_ids = array();
			if(isset($item)) {
				if($item->isA(CS_LABEL_TYPE) && $item->getLabelType() == CS_GROUP_TYPE) {
					$group_manager = $this->_environment->getGroupManager();
					$item = $group_manager->getItem($item_id);
					unset($group_manager);
				} elseif($item->isA(CS_LABEL_TYPE) && $item->getLabelType() == CS_BUZZWORD_TYPE) {
					$buzzword_manager = $this->_environment->getBuzzwordManager();
					$item = $buzzword_manager->getItem($item_id);
					unset($buzzword_manager);
				}
				
				if($module == CS_USER_TYPE) {
					if($this->_environment->inCommunityRoom()) $selected_ids = $item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
					else $selected_ids = $item->getLinkedItemIDArray(CS_GROUP_TYPE);
				} elseif($item->isA(CS_LABEL_TYPE) && $item->getLabelType() == CS_BUZZWORD_TYPE) {
					$selected_ids = $item->getAllLinkedItemIDArrayLabelVersion();
				} else {
					$selected_ids = $item->getAllLinkedItemIDArray();
				}
			}
			
			/*
// initial
if ( !$session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2') ) {
   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2',$selected_ids);
}

if ( !empty($_POST['itemlist'])
     or !empty($_POST['shown'])
   ) {
   $sess_selected_ids = array();
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2')) {
      $sess_selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
   }
   if ( !empty($_POST['itemlist']) ) {
      foreach ($_POST['itemlist'] as $key => $id) {
         $sess_selected_ids[] = $key;
      }
   }
   if ( !empty($_POST['shown']) ) {
      $drop_array = array();
      foreach ( $_POST['shown'] as $id => $value) {
         if ( in_array($id,$sess_selected_ids)
              and ( empty($_POST['itemlist'])
                    or !array_key_exists($id,$_POST['itemlist'])
                  )
            ) {
            $drop_array[] = $id;
         }
      }
      if ( !empty($drop_array) ) {
         $temp_array = array();
         foreach ($sess_selected_ids as $id) {
            if ( !in_array($id,$drop_array) ) {
               $temp_array[] = $id;
            }
         }
         $sess_selected_ids = $temp_array;
      }
   }
   $sess_selected_ids = array_unique($sess_selected_ids);
   $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2',$sess_selected_ids);
}

if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2') ) {
   $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
}

if ($mode == '') {
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
}

// wie komme ich von einer liste über die actions hier her ???
// 2009.07.24 ij
elseif ( $mode == 'list_actions' ) {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
   }
}

// wird das noch gebraucht????
// ij 16.10.2009
if ( isset($_COOKIE['itemlist']) ) {
   foreach ( $_COOKIE['itemlist'] as $key => $val ) {
      setcookie ('itemlist['.$key.']', '', time()-3600);
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

$session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$selected_ids);

if ( isset($_POST['right_box_option2']) ) {
   $right_box_command2 = $_POST['right_box_option2'];
} elseif ( isset($_GET['right_box_option2']) ) {
   $right_box_command2 = $_GET['right_box_option2'];
} else {
   $right_box_command2 = '';
}

$browse_dir = '';
if ( strstr($right_box_command2, '_START') ) {
   $browse_dir = '_start';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
} elseif ( strstr($right_box_command2, '_LEFT') ) {
   $browse_dir = '_left';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
} elseif ( strstr($right_box_command2, '_RIGHT') ) {
   $browse_dir = '_right';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
} elseif ( strstr($right_box_command2, '_END') ) {
   $browse_dir = '_end';
   #$right_box_command = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
}

// Find current browsing starting point
if ( isset($_POST['from'.$browse_dir]) ) {
   $from = $_POST['from'.$browse_dir];
} elseif ( isset($_GET['from']) ) {
   $from = $_GET['from'];
} elseif ( isset($_POST['from']) ) {
   $from = $_POST['from'];
} else {
   $from = 1;
}

// Find current browsing interval
// The browsing interval is applied to all rubrics!
if ( isset($_GET['interval']) ) {
   $interval = $_GET['interval'];
} elseif ( isset($_POST['interval']) ) {
   $interval = $_POST['interval'];
} else {
   $interval = CS_LIST_INTERVAL;
}

if ( !empty($option)
      and (isOption($option, $translator->getMessage('COMMON_ITEM_ATTACH')))
    ) {
    $entry_array = array();
    $entry_new_array = array();
    if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_linked_items_index_selected_ids')) {
       $entry_array = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_linked_items_index_selected_ids');
    }
    if (isset($_POST['itemlist'])){
       $selected_id_array = $_POST['itemlist'];
       foreach($selected_id_array as $id => $value){
          $entry_new_array[] = $id;
       }
    }
    $entry_array = array_merge($entry_array,$entry_new_array);
    $entry_array = array_unique($entry_array);
    if ( isset($item)
         and $item->isA(CS_LABEL_TYPE)
         and $item->getLabelType() == CS_BUZZWORD_TYPE
       ) {
       $item->saveLinksByIDArray($entry_array);
    } elseif ( isset($item) )  {
       $item->setLinkedItemsByIDArray($entry_array);
       $item->save();
    }
    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
    unset($params['attach_view']);
    unset($params['attach_type']);
    unset($params['from']);
    unset($params['pos']);
    unset($params['mode']);
    unset($params['return_attach_item_list']);
    if ( $environment->getCurrentModule() == type2module(CS_DATE_TYPE) ) {
       unset($params['date_option']);
    }
    if ( $environment->getCurrentModule() == type2module(CS_TODO_TYPE) ) {
       unset($params['todo_option']);
    }
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
}
*/
			
			// get current room modules
			$room_modules = array();
			$current_room_modules = $current_context->getHomeConf();
			if(!empty($current_room_modules)) $room_modules = explode(',', $current_room_modules);
			
			$rubric_array = array();
			foreach($room_modules as $room_module) {
				list($name, $display) = explode('_', $room_module);
				
				if($display != 'none'	&&	!($this->_environment->inPrivateRoom() && $name == 'user') &&
						!(	$name == CS_USER_TYPE && (
								$module == CS_MATERIAL_TYPE ||
								$module == CS_DISCUSSION_TYPE ||
								$module == CS_ANNOUNCEMENT_TYPE ||
								$module == CS_TOPCI_TYPE))) {
					$rubric_array[] = $name;
				}
			}
			
			/*
			 * 
			 * if ( !empty($selrubric)
			     and $selrubric != 'all'
			     and $selrubric != 'campus_search'
			     and $selrubric != -1
			   ) {
			   $rubric_array = array();
			   $rubric_array[] = $selrubric;
			}

*/
			
			if($module == CS_USER_TYPE) {
				$rubric_array = array();
				
				if($current_context->withRubric(CS_GROUP_TYPE)) $rubric_array[] = CS_GROUP_TYPE;
				if($current_context->withRubric(CS_INSTITUTION_TYPE)) $rubric_array[] = CS_INSTITUTION_TYPE;
				
				// $interval = 100;
			}
			
			// build item list
			$item_list = new cs_list();
			$item_ids = array();
			$count_all = 0;
			foreach($rubric_array as $rubric) {
				$rubric_list = new cs_list();
				$rubric_manager = $this->_environment->getManager($rubric);
				
				if(isset($rubric_manager) && $rubric != CS_MYROOM_TYPE) {
					if($rubric != CS_PROJECT_TYPE) $rubric_manager->setContextLimit($this->_environment->getCurrentContextID());
					
					if($rubric == CS_DATE_TYPE) $rubric_manager->setWithoutDateModeLimit();
					
					if($rubric == CS_USER_TYPE) {
						$rubric_manager->setUserLimit();
						
						if($current_user->isUser()) $rubric_manager->setVisibleToAllAndCommsy();
						else $rubric_manager->setVisibleToAll();
					}
					
					$count_all += $rubric_manager->getCountAll();
					
					// set restrictions
					if(!empty($restrictions['search'])) $rubric_manager->setSearchLimit($search);
					if($restrictions['only_linked'] === true) $rubric_manager->setIDArrayLimit($selected_ids);
					if($restrictions['type'] == 2) $rubric_manager->showNoNotActivatedEntries();
					
					$rubric_manager->selectDistinct();
					$rubric_list = $rubric_manager->get();
					
					// show hidden entries only if user is moderator or owner
					if($restrictions['type'] != 2 && !$current_user->isModerator()) {
						// check if user is owner
						$entry = $rubric_list->getFirst();
						while($entry) {
							if($entry->isNotActivated() && $entry->getCreatorID() != $current_user->getItemID()) {
								// remove item from list
								$rubric_list->removeElement($entry);
							}
							
							$entry = $rubric_list->getNext();
						}
					}
					
					// add rubric list to item list
					$item_list->addList($rubric_list);
					
					$temp_rubric_ids = $rubric_manager->getIDArray();
					if(!empty($temp_rubric_ids)) {
						//$session->setValue('cid'.$environment->getCurrentContextID().'_item_attach_index_ids', $rubric_ids);
						$item_ids = array_merge($item_ids, $temp_rubric_ids);
					}
				}
			}
			
			$interval = CS_LIST_INTERVAL;
			$from = $current_page * $interval;
			
			// get sublist - paging
			$sublist = $item_list->getSublist($from, $interval);
			
			// prepare return
			$return['list'] = array();
			$item = $sublist->getFirst();
			while($item) {
				$entry = array();
				
				$entry['title']				= $item->getTitle();
				$entry['modification_date']	= $item->getModificationDate();
				$entry['modificator']		= $item->getModificatorItem()->getFullName();
				
				$return['list'][] = $entry;
				$item = $sublist->getNext();
			}
			$return['paging']['pages'] = ceil(/*$count_all*/count($item_ids) / $interval);
			
			/*

$sublist = $item_list->getSubList($from-1,$interval);
$item_attach_index_view->setList($sublist);

$item_attach_index_view->setLinkedItemIDArray($selected_ids);
$item_attach_index_view->setRefItemID($ref_iid);
$item_attach_index_view->setRefItem($item);
$item_attach_index_view->setCountAllShown(count($item_ids));
$item_attach_index_view->setCountAll($count_all);
$item_attach_index_view->setFrom($from);
$item_attach_index_view->setInterval($interval);
$item_attach_index_view->setSearchText($search);
$item_attach_index_view->setChoosenRubric($selrubric);
$item_attach_index_view->setActivationLimit($sel_activating_status);
			 */
			
			$return['success'] = true;
			
			echo json_encode($return);
		}
		
		public function actionGetInitialData() {
			$return = array();
			
			// get request data
			$module = $this->_data['module'];
			
			$current_context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();
			
			// get available rubrics
			$rubrics = array();
			
			// add all
			$rubrics[] = array(
				'value'		=> 'all',
				'text'		=> $translator->getMessage('ALL'),
				'disabled'	=> false
			);
			
			// add disabled
			$rubrics[] = array(
				'value'		=> '-1',
				'text'		=> '-------------------------',
				'disabled'	=> true
			);
			
			// add rubrics
			$current_room_modules = $current_context->getHomeConf();
			$room_modules = array();
			if(!empty($current_room_modules)) $room_modules = explode(',', $current_room_modules);
			
			foreach($room_modules as $module) {
				list($name, $display) = explode('_', $module);
				
				if($display != 'none'	&& (	$name != CS_USER_TYPE || (	$module != CS_MATERIAL_TYPE &&
																			$module != CS_DISCUSSION_TYPE &&
																			$module != CS_ANNOUNCEMENT_TYPE &&
																			$module != CS_TOPIC_TYPE))
										&& $name != CS_PROJECT_TYPE
										&& !$this->_environment->isPlugin($name)
										&& !($this->_environment->inPrivateRoom() && $name == CS_MYROOM_TYPE)) {
					// determ rubric text
					switch(mb_strtoupper($name, 'UTF-8')) {
						case 'ANNOUNCEMENT':
							$text = $translator->getMessage('ANNOUNCEMENT_INDEX');
							break;
						case 'DATE':
							$text = $translator->getMessage('DATE_INDEX');
							break;
						case 'DISCUSSION':
							$text = $translator->getMessage('DISCUSSION_INDEX');
							break;
						case 'GROUP':
							$text = $translator->getMessage('GROUP_INDEX');
							break;
						case 'INSTITUTION':
							$text = $translator->getMessage('INSTITUTION_INDEX');
							break;
						case 'MATERIAL':
							$text = $translator->getMessage('MATERIAL_INDEX');
							break;
						case 'PROJECT':
							$text = $translator->getMessage('PROJECT_INDEX');
							break;
						case 'TODO':
							$text = $translator->getMessage('TODO_INDEX');
							break;
						case 'TOPIC':
							$text = $translator->getMessage('TOPIC_INDEX');
							break;
						case 'USER':
							$text = $translator->getMessage('USER_INDEX');
							break;
						default:
							$text = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' ('.$name.') '.__FILE__.'('.__LINE__.') ' );
							break;
					}
					
					// add rubric
					$rubrics[] = array(
						'value'		=> $name,
						'text'		=> $text,
						'disabled'	=> false
					);
				}
			}
			
			// append to return
			$return['rubrics'] = $rubrics;
			
			// only linked items checkbox
			
			/*

      # checkbox for only linked items
      // jQuery
      //$html .= '   <input type="checkbox" name="linked_only" value="1" onChange="javascript:document.item_list_form.submit()"';
      $html .= '   <input type="checkbox" name="linked_only" value="1" id="submit_form"';
      // jQuery
      if ( !empty($_POST['linked_only']) and $_POST['linked_only'] == 1 ) {
         $html .= ' checked="checked"';
      }
      $html .= '/>'.$this->_translator->getMessage('SEARCH_LINKED_ENTRIES_ONLY').BRLF;
      */
			// search field
			
			/*

      # textfield for search term
      $html .= '   <input type="textfield" name="search" style="width: 135px;"';
      if ( !empty($_POST['search']) ) {
         $html .= ' value="'.$this->_text_as_form($_POST['search']).'"';
      }
      $html .= '/>'.LF;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '   <input src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align: top;" alt="Suchen" type="image">'.LF;
      } else {
         $html .= '   <input src="images/commsyicons/22x22/search.png" style="vertical-align: top;" alt="Suchen" type="image">'.LF;
      }

      # div end
      $html .= '</div>'.LF;
      */
				
			$return['success'] = true;
				
			echo json_encode($return);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}