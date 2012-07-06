<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_tags_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionCreateNewTag() {
			if($this->accessGranted()) {
				$current_user = $this->_environment->getCurrentUserItem();
				
				$tagName = trim($this->_data["tagName"]);
				$parentId = $this->_data["parentId"];
				
				// check if empty
				if($tagName === "") {
					$this->setErrorReturn("110", "tag is empty", array());
					echo $this->_return;
				} else {
					$tag_manager = $this->_environment->getTagManager();
					
					$tag_item = $tag_manager->getNewItem();
					$tag_item->setTitle($tagName);
					$tag_item->setContextID($this->_environment->getCurrentContextID());
					$tag_item->setCreatorItem($current_user);
					$tag_item->setCreationDate(getCurrentDateTimeInMySQL());
					$tag_item->setPosition($parentId, 1);
					$tag_item->save();
					
					$this->setSuccessfullDataReturn(array("tagId" => $tag_item->getItemID()));
					echo $this->_return;
				}
			}
		}
		
		public function actionRenameTag() {
			if($this->accessGranted()) {
				$current_user = $this->_environment->getCurrentUserItem();
		
				$newTagName = trim($this->_data["newTagName"]);
				$tagId = $this->_data["tagId"];
		
				// check if empty
				if($newTagName === "") {
					$this->setErrorReturn("110", "tag is empty", array());
					echo $this->_return;
				} else {
					$tag_manager = $this->_environment->getTagManager();
					
					$tag_item = $tag_manager->getItem($tagId);
					$tag_item->setTitle($newTagName);
					$tag_item->setModificatorItem($current_user);
					$tag_item->setModificationDate(getCurrentDateTimeInMySQL());
					$tag_item->save();
						
					$this->setSuccessfullDataReturn(array());
					echo $this->_return;
				}
			}
		}
		
		public function actionUpdateTreeStructure() {
			if($this->accessGranted()) {
				$parentId = $this->_data["parentId"];
				$children = $this->_data["children"];
				
				$tag_manager = $this->_environment->getTagManager();
				$tag2tag_manager = $this->_environment->getTag2TagManager();
				
				// process all children
				foreach($children as $childIndex => $childId) {
					// get item
					$childItem = $tag_manager->getItem($childId);
					
					// delete all references from/to this child id
					// ...?
					
					// set new position
					$childItem->setPosition($parentId, $childIndex + 1);
					
					// save
					$childItem->save();
				}
				
				$this->setSuccessfullDataReturn(array());
				echo $this->_return;
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
			
			if (!$current_user->isUser() || ( !$current_context->isTagEditedByAll() && !$current_user->isModerator)) {
				return false;
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
				return true;
			}
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