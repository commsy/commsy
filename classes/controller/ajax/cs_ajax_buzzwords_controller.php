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
				
				// check if empty
				if(empty($buzzword)) {
					$this->setErrorReturn("108", "buzzword is empty", array());
					echo $this->_return;
				} else {
					// get current buzzwords and check for duplicates
					$currBuzzwords = $this->getUtils()->getBuzzwords(true);
					
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
						$buzzword_item->setContextID($this->_environment->getCurrentContextID());
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
						"buzzwordOne" => $buzzwordOne,
						"buzzwordTwo" => $buzzwordTwo,
						"newBuzzword" => $newName)
					);
					echo $this->_return;
				}
			}
		}
		
		
		
		
		
		
		
		
		public function actionDelete() {
			if($this->accessGranted()) {
				$buzzword_id = $this->_data['form_data']['buzzword_id'];
				
				$buzzword_manager = $this->_environment->getLabelManager();
				$buzzword_item = $buzzword_manager->getItem($buzzword_id);
				if(!empty($buzzword_item)) {
					$buzzword_item->delete();
					
					echo json_encode('success');
					return true;
				} else {
					echo json_encode('item was empty');
					return false;
				}
			}
		}
		
		public function actionChange() {
			if($this->accessGranted()) {
				$buzzword_id = $this->_data['form_data']['buzzword_id'];
				$buzzword = $this->_data['form_data']['buzzword'];				
				
				$buzzword_manager = $this->_environment->getLabelManager();
				$buzzword_item = $buzzword_manager->getItem($buzzword_id);
				if(!empty($buzzword_item)) {
					$buzzword_item->setName($buzzword);
					$buzzword_item->save();
					
					echo json_encode('success');
					return true;
				} else {
					echo json_encode('item was empty');
					return false;
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