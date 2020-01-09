<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_buzzwords_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionCreateNewBuzzword() {
			if($this->accessGranted()) {
				$current_user = $this->_environment->getCurrentUserItem();
				
				$buzzword = trim($this->_data["buzzword"]);
				$roomId = $this->_data["roomId"];
				
				// check if empty
				if($buzzword === "") {
					$this->setErrorReturn("108", "buzzword is empty", array());
					echo $this->_return;
				} else {
					// get current buzzwords and check for duplicates
					if ($roomId == null) {
						$currBuzzwords = $this->getUtils()->getBuzzwords(true);
					} else {
						$currBuzzwords = $this->getUtils()->getBuzzwords(true, $roomId);
					}
					
					$isDuplicate = false;
					foreach($currBuzzwords as $currBuzzword) {
							
						if($currBuzzword["name"] === $buzzword) {
							$isDuplicate = true;
							break;
						}
					}
					
					// if duplicate return an error, otherwise create new buzzword
					if($isDuplicate) {
						$this->setErrorReturn("107", "buzzword already exists", array());
						echo $this->_return;
					} else {
						$buzzword_manager = $this->_environment->getLabelManager();
							
						$buzzword_item = $buzzword_manager->getNewItem();
						$buzzword_item->setLabelType('buzzword');
						$buzzword_item->setName($buzzword);
						if ($roomId == null) {
							$buzzword_item->setContextID($this->_environment->getCurrentContextID());
						} else {
							$buzzword_item->setContextID($roomId);
						}
						$buzzword_item->setCreatorItem($current_user);
						$buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
						$buzzword_item->save();
							
						$this->setSuccessfullDataReturn(array("id" => $buzzword_item->getItemID()));
						echo $this->_return;
					}
				}
			}
		}
		
		public function actionMergeBuzzwords() {
			if($this->accessGranted()) {
				$buzzwordIdOne = $this->_data["idOne"];
				$buzzwordIdTwo = $this->_data["idTwo"];
				
				// check if both are different from each other
				if($buzzwordIdOne == $buzzwordIdTwo) {
					$this->setErrorReturn("108", "can't merge two buzzwords with same id", array());
					echo $this->_return;
				} else {
					// merge them
					$link_manager = $this->_environment->getLinkManager();
					$link_manager->combineBuzzwords($buzzwordIdOne, $buzzwordIdTwo);
					
					// get both
					$buzzword_manager = $this->_environment->getLabelManager();
					$buzzwordItemOne = $buzzword_manager->getItem($buzzwordIdOne);
					$buzzwordItemTwo = $buzzword_manager->getItem($buzzwordIdTwo);
					
					// change name of item one, save it and delete the item two
					$buzzwordOne = $buzzwordItemOne->getName();
					$buzzwordTwo = $buzzwordItemTwo->getName();
					$newName = $buzzwordOne. "/" . $buzzwordTwo;
					$buzzwordItemOne->setName($newName);
					$buzzwordItemOne->setModificationDate(getCurrentDateTimeInMySQL());
					$buzzwordItemOne->save();
					$buzzwordItemTwo->delete();
					
					$this->setSuccessfullDataReturn(array(
						"buzzwordOne"	=> $buzzwordOne,
						"buzzwordTwo"	=> $buzzwordTwo,
						"newBuzzword"	=> $newName)
					);
					echo $this->_return;
				}
			}
		}
		
		public function actionGetBuzzwords() {
			$roomId = $this->_data["roomId"];
			
			$buzzwords = $this->getUtils()->getBuzzwords(true, $roomId);
			
			$this->setSuccessfullDataReturn($buzzwords);
			echo $this->_return;
		}
		
		public function actionGetInitialData() {
			$return = array();
		
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
				
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionPerformRequest() {
			$return = array();
		
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUser();
			$session = $this->_environment->getSessionItem();
			$item_manager = $this->_environment->getItemManager();
		
			// get request data
			$item_id = $this->_data['item_id'];
			$module = $this->_data['module'];
			$current_page = $this->_data['current_page'];
			$restrictions = $this->_data['restrictions'];
			$roomId = $this->_data["roomId"];
			
			// get item
			$item = $item_manager->getItem($item_id);
			if (isset($item)) {
				$manager = $this->_environment->getManager($item->getItemType());
				$item = $manager->getItem($item_id);
			}
			
			$selected_ids = array();
			if (isset($item)) {
				if ($item->isA(CS_LABEL_TYPE) && $item->getLabelType() == CS_GROUP_TYPE) {
					/*
					 * $group_manager = $environment->getGroupManager();
      $item = $group_manager->getItem($ref_iid);
      unset($group_manager);
					 */
				} elseif ($item->isA(CS_LABEL_TYPE) && $item->getLabelType() == CS_BUZZWORD_TYPE) {
					$buzzword_manager = $this->_environment->getBuzzwordManager();
					$item = $buzzword_manager->getItem($item_id);
				}
				
				/*
				 * if ($environment->getCurrentModule() == CS_USER_TYPE){
				      if ($environment->inCommunityRoom()){
				         $selected_ids = $item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
				      }else{
				         $selected_ids = $item->getLinkedItemIDArray(CS_GROUP_TYPE);
				      }
				   } elseif ( $item->isA(CS_LABEL_TYPE)
				              and $item->getLabelType() == CS_BUZZWORD_TYPE
				            ) {
				      $selected_ids = $item->getAllLinkedItemIDArrayLabelVersion();
				   } else {
				      $selected_ids = $item->getAllLinkedItemIDArray();
				   }
				 */
				$selected_ids = $item->getAllLinkedItemIDArrayLabelVersion();
			}
			
			
			/*

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




*/
			
			// build item list
			$item_list = new cs_list();
			$item_ids = array();
			$count_all = 0;
			
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
								$module == CS_TOPIC_TYPE))) {
					$rubric_array[] = $name;
				}
			}
			
			if($module == CS_USER_TYPE) {
				$rubric_array = array();
			
				if($current_context->withRubric(CS_GROUP_TYPE)) $rubric_array[] = CS_GROUP_TYPE;
			
				// $interval = 100;
			}
			
			// perform rubric restriction
			if(!empty($restrictions['rubric']) && $restrictions['rubric'] !== "all") {
				$rubric_array = array();
				$rubric_array[] = $restrictions['rubric'];
			}
			
			if($restrictions['only_linked'] === true && empty($selected_ids)) $rubric_array = array();
			
			foreach($rubric_array as $rubric) {
				$rubric_list = new cs_list();
				$rubric_manager = $this->_environment->getManager($rubric);
			
				if(isset($rubric_manager) && $rubric != CS_MYROOM_TYPE) {
					if($rubric != CS_PROJECT_TYPE) {
						if ($roomId == null) {
							$rubric_manager->setContextLimit($this->_environment->getCurrentContextID());
						} else {
							$rubric_manager->setContextLimit($roomId);
						}
					}
			
					if($rubric == CS_DATE_TYPE) $rubric_manager->setWithoutDateModeLimit();
			
					if($rubric == CS_USER_TYPE) {
						$rubric_manager->setUserLimit();
			
						if($current_user->isUser()) $rubric_manager->setVisibleToAllAndCommsy();
						else $rubric_manager->setVisibleToAll();
					}
			
					$count_all += $rubric_manager->getCountAll();
			
					// set restrictions
					if(!empty($restrictions['search'])) $rubric_manager->setSearchLimit($restrictions['search']);
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
		
				$entry['item_id']			= $item->getItemID();
				$entry['title']				= $item->getTitle();
				$entry['modification_date']	= $item->getModificationDate();
				$entry['modificator']		= $item->getModificatorItem()->getFullName();
				$entry['system_label']		= $item->isSystemLabel();
		
				$entry['checked'] = false;
				if(in_array($item->getItemID(), $selected_ids)) $entry['checked'] = true;
		
				$return['list'][] = $entry;
				$item = $sublist->getNext();
			}
			$return['paging']['pages'] = ceil(/*$count_all*/count($item_ids) / $interval);
			$return['num_selected_total'] = count($selected_ids);
				
			$this->setSuccessfullDataReturn($return);
			echo $this->_return;
		}
		
		public function actionUpdateLinkedItem() {
			$return = array();
			
			$item_manager = $this->_environment->getItemManager();
		
			// get request data
			$item_id = $this->_data['item_id'];
			$link_id = $this->_data['link_id'];
			$checked = $this->_data['checked'];
			$contextId = $this->_data["contextId"];
				
			if ($contextId !== null) {
				$this->_environment->changeContextToPrivateRoom($contextId);
			}
			
			// get item
			$item = $item_manager->getItem($item_id);
			if (isset($item)) {
				$manager = $this->_environment->getManager($item->getItemType());
				$item = $manager->getItem($item_id);
			}
				
			$selected_ids = array();
			if (isset($item)) {
				if (!$item->isA(CS_LABEL_TYPE) || $item->getLabelType() != CS_BUZZWORD_TYPE) {
					$this->setErrorReturn("108", "wrong item type, buzzword expected", array());
					echo $this->_return;
					exit;
				}

				$buzzword_manager = $this->_environment->getBuzzwordManager();
				$item = $buzzword_manager->getItem($item_id);

				$selected_ids = $item->getAllLinkedItemIDArrayLabelVersion();
			}
			
			if($checked === true) {
				// add
				$selected_ids[] = $link_id;
				$selected_ids = array_unique($selected_ids);
			} else {
				// remove
				$tmp_array = array();
				foreach ($selected_ids as $id) {
					if ($link_id != $id) {
						$tmp_array[] = $id;
					}
				}
				
				$selected_ids = $tmp_array;
			}
			
			// save
			$item->saveLinksByIDArray($selected_ids);
		
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionUpdateBuzzword() {
			if($this->accessGranted()) {
				$buzzword_id = $this->_data['buzzword_id'];
				$buzzword = $this->_data['buzzword'];
				$contextId = $this->_data["contextId"];
				
				if ($contextId !== null) {
					$this->_environment->changeContextToPrivateRoom($contextId);
				}
			
				$buzzword_manager = $this->_environment->getLabelManager();
				$buzzword_item = $buzzword_manager->getItem($buzzword_id);
				if(!empty($buzzword_item)) {
					$buzzword_item->setName($buzzword);
					$buzzword_item->save();
					
					$this->setSuccessfullDataReturn(array());
					echo $this->_return;
				} else {
					$this->setErrorReturn("109", "item was empty", array());
					echo $this->_return;
				}
			}
		}
		
		public function actionDeleteBuzzword() {
			if($this->accessGranted()) {
				$buzzword_id = $this->_data['buzzword_id'];
		
				$buzzword_manager = $this->_environment->getLabelManager();
				$buzzword_item = $buzzword_manager->getItem($buzzword_id);
				if(!empty($buzzword_item)) {
					$buzzword_item->delete();
						
					$this->setSuccessfullDataReturn(array());
					echo $this->_return;
				} else {
					$this->setErrorReturn("109", "item was empty", array());
					echo $this->_return;
				}
			}
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
		
		private function accessGranted() {
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
			
			/*
			// Get linked rubric
if ( !empty($_GET['module']) ) {
   $linked_rubric = $_GET['module'];
   $session->setValue($environment->getCurrentModule().'_linked_rubric',$linked_rubric);
} elseif ( $session->issetValue($environment->getCurrentModule().'_linked_rubric') ) {
   $linked_rubric = $session->getValue($environment->getCurrentModule().'_linked_rubric');
} else {
   $linked_rubric = '';
}
			 */
			
			// check access rights
			if(!$current_user->isUser()) {
				/*
				 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
				 */ 
				return false;
			} /*elseif ( empty($linked_rubric) ){
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('BUZZWORD_MISSING_LINKED_RUBRIC'));
   $page->add($errorbox);
}
			
			return false;
			*/
			
			// access granted
			else {
				return true;
			}
		}
		
		private function cleanupSession($iid) {
			$session = $this->_environment->getSessionItem();
			
			$session->unsetValue($this->_environment->getCurrentModule() . '_add_files');
			$session->unsetValue($iid . '_post_vars');
			$session->unsetValue($iid . '_material_attach_ids');
			$session->unsetValue($iid . '_institution_attach_ids');
			$session->unsetValue($iid . '_group_attach_ids');
			$session->unsetValue($iid . '_topic_attach_ids');
			$session->unsetValue($iid . '_material_back_module');
			$session->unsetValue($iid . '_institution_back_module');
			$session->unsetValue($iid . '_group_back_module');
			$session->unsetValue($iid . '_topic_back_module');
		}
	}
?>

<?php
/*
				 * // Find out what to do
   $iid = 0;
   $delete_iid = 0;
   $command = '';
   $delete_option = '';
   if(isset($_POST)) {
      foreach($_POST as $key => $value) {
         if(empty($command) && mb_substr($key, 0, 6) == 'option') {
            $command = $value;
            $iid = mb_substr($key, 7);
         }
         
         if(empty($delete_option) && mb_substr($key, 0, 13) == 'delete_option') {
            $delete_option = $value;
            $delete_iid = mb_substr($key, 14);
         }
         
         if(!empty($command) && !empty($delete_option)) {
            break;
         }
      }
   }
   
   // delete box
   $deleteOverlay = false;
   if(isOption($command, $translator->getMessage('COMMON_DELETE_BUTTON'))) {
      $params = $environment->getCurrentParameterArray();
      $params['delete_id'] = $iid;
	  $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
	  $deleteOverlay = true;
   }
   // change option
   else if(isOption($command, $translator->getMessage('BUZZWORDS_CHANGE_BUTTON'))) {
      $change_id = $iid;
   }
   
   ##########################################
   ## handle messages from delete box
   #
   // delete option
   if(isOption($delete_option, $translator->getMessage('COMMON_DELETE_BUTTON'))) {
      if(isset($_GET['delete_id'])) {
         $delete_id = $_GET['delete_id'];
      } else {
         $delete_id = $delete_iid;
      }
      
   }
   // cancel option
   else if(isOption($delete_option, $translator->getMessage('COMMON_CANCEL_BUTTON'))) {
      $params = $environment->getCurrentParameterArray();
      unset($params['delete_id']);
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
   }
   #
   ##
   ##########################################

   // attach items
   if ( !empty($_POST) && !$deleteOverlay ) {
      $link_items = false;
      foreach ( $_POST as $key => $value ) {
         if ( $value == $translator->getMessage('COMMON_ITEM_NEW_ATTACH')
              and strstr($key,'right_box_option')
            ) {
            $tag_id = substr($key,strpos($key,'#')+1);
            $_GET['iid'] = $tag_id;
            if ( !empty($_POST['module'])
                 and $_POST['module'] != 'home'
               ) {
               $_GET['selrubric'] = $_POST['module'];
            }
            $_POST['right_box_option'] = $translator->getMessage('COMMON_ITEM_NEW_ATTACH');
            $link_items = true;
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids2');
            break;
         }
      }
      if ( !$link_items
           and !empty($_POST['option'])
           and isOption($_POST['option'], $translator->getMessage('COMMON_ITEM_ATTACH'))
         ) {
         $link_items = true;
      }
      if ( !$link_items
           and !empty($_POST)
           and empty($_POST['option'])
           and empty($change_id)
           and empty($delete_id)
         ) {
         $_GET['attach_view'] = 'yes';
         $_GET['attach_type'] = 'item';
         $link_items = true;
      }
      if ( $link_items ) {
         include_once('pages/item_attach.php');
      }
   }
   
   // Show form and/or save item
   // Initialize the form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(BUZZWORDS_FORM,$class_params);
   unset($class_params);
   
   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   }
   
   $form->prepareForm();
   $form->loadValues();
   
   // Save item
   if ( !empty($delete_id) or !empty($change_id) ) {
      $buzzword_manager  = $environment->getLabelManager();
      // delete
      if(isset($delete_id) && !empty($delete_id)) {
         $buzzword_item = $buzzword_manager->getItem($delete_id);
         if(!empty($buzzword_item)) {
            $buzzword_item->delete();
         }
         unset($delete_id);
         unset($tag_item);
      }
      // change
      else if(isset($change_id) && !empty($change_id)) {
         $buzzword_item = $buzzword_manager->getItem($change_id);
         if(!empty($buzzword_item)) {
            $buzzword_item->setName($_POST['buzzword#' . $change_id]);
            $buzzword_item->save();
         }
         unset($change_id);
         unset($tag_item);
      }
      unset($tag_manager);
      
      $params = array();
      if (empty($delete_id)) {
         $params['focus_element_onload'] = $change_id;
      }
      redirect($environment->getCurrentContextID(),'buzzwords', 'edit', $params);
   }elseif (!empty($command) and isOption($command, $translator->getMessage('BUZZWORDS_NEW_BUTTON'))){
      
   }elseif (!empty($command) and isOption($command, $translator->getMessage('BUZZWORDS_COMBINE_BUTTON'))){
      
   }

  
				 */
				 ?>