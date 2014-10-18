<?php
	require_once('classes/controller/cs_edit_controller.php');

	class cs_step_edit_controller extends cs_edit_controller {
		private $_item = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'step_edit';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_DISCUSSION_TYPE);
		}
		
		protected function getFieldInformation() {
			return array(
				array(	'name'		=> 'title',
						'type'		=> 'text',
						'mandatory' => true),
				array(	'name'		=> 'ckeditor_step',
						'type'		=> 'text',
						'mandatory'	=> false)
			);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionEdit() {
			$session = $this->_environment->getSessionItem();
			$translator = $this->_environment->getTranslationObject();
			
			// get the current user and room
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
			
			// get post data
			$this->getPostData();
			
			// check access rights
			if($current_context->isProjectRoom() && $current_context->isClosed()) {
				/*
				 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
				 */
			} elseif($this->_item_id !== "NEW" && !isset($this->_item)) {
				/*
				 * $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
				 */
			} elseif(	!(($this->_item_id === "NEW" && $current_user->isUser()) ||
						($this->_item_id !== "NEW" && isset($this->_item) && $this->_item->mayEdit($current_user))) ) {
				
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
				
				if(	$this->_command !== null &&
						(isOption($this->_command, CS_OPTION_SAVE) ||
						isOption($this->_command, CS_OPTION_CHANGE) ||
						isOption($this->_command, CS_OPTION_NEW))) {
						
					if($this->checkFormData()) {
						if(!isset($step_item)) {
							$step_manager = $this->_environment->getStepManager();
							$step_item = $step_manager->getNewItem();
							$step_item->setContextID($this->_environment->getCurrentContextID());
							$user = $this->_environment->getCurrentUserItem();
							$step_item->setCreatorItem($user);
							$step_item->setCreationDAte(getCurrentDateTimeInMySQL());
							$step_item->setTodoID($_POST["todo_id"]);
						}
						
						$todo_manager = $this->_environment->getTodoManager();
						$todo_item = $todo_manager->getItem($_POST["todo_id"]);
						
						// set modificator and modification date
						$user = $this->_environment->getCurrentUserItem();
						$step_item->setModificatorItem($user);
						$step_item->setModificationDate(getCurrentDateTimeInMySQL());
						
						// set attributes
						if(isset($_POST["form_data"]["title"])) $step_item->setTitle($_POST["form_data"]["title"]);
						
						if(isset($_POST["form_data"]["ckeditor_step"])) $step_item->setDescription($_POST["form_data"]["ckeditor_step"]);
						
						if(isset($_POST["form_data"]["minutes"])) {
							$minutes = $_POST["form_data"]["minutes"];
							$minutes = str_replace(",", ".", $minutes);
							
							if(isset($_POST["form_data"]["time_type"])) {
								$step_item->setTimeType($_POST["form_data"]["time_type"]);
								
								switch($_POST["form_data"]["time_type"]) {
									case 2: $minutes = $minutes * 60; break;
									case 3: $minutes = $minutes * 60 * 8; break;
								}
							}
							
							$step_item->setMinutes($minutes);
						}
						
						// save
						$step_item->save();
						
						$status = $todo_item->getStatus();
						if($status == $translator->getMessage("TODO_NOT_STARTED")) {
							$todo_item->setStatus(2);
						}
						$todo_item->setModificationDate(getCurrentDateTimeInMySQL());
						$todo_item->save();
						
						/*
            // Set links to connected rubrics
            if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
               $step_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
            } else {
               $step_item->setMaterialListByID(array());
            }

            $item_files_upload_to = $step_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');
						*/
						
						// redirect
						$this->cleanup_session($this->_item_id);
						redirect($this->_environment->getCurrentContextID(), "todo", "detail", array("iid" => $step_item->getTodoID()), "step".$step_item->getItemID());
					} else {
						// store description in session
						$sessionKey = 'cid' . $this->_environment->getCurrentContextID() . '_step_last_description';
						$session->setValue($sessionKey, $_POST['form_data']['ckeditor_step']);

						redirect($this->_environment->getCurrentContextID(), "todo", "detail", array("iid" => $_POST["todo_id"], "step_exception" => "mandatory"), "step_new");
					}
				}
			}
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function getDetailContent() {
			/*
			$disc_articles = $this->getDiscArticleContent();
			
			$return = array(
				'discussion'		=> $this->getDiscussionContent(),
				'disc_articles'		=> $disc_articles,
				'new_num'			=> count($disc_articles) + 1
			);
			
			return $return;
			*/
		}
		
		private function cleanup_session($current_iid) {
			$session = $this->_environment->getSessionItem();
			$session->unsetValue($this->_environment->getCurrentModule().'_add_files');
			$session->unsetValue($current_iid.'_post_vars');
			$session->unsetValue($current_iid.'_material_attach_ids');
			$session->unsetValue($current_iid.'_material_back_module');
			$session->unsetValue($current_iid.'_step_last_description');
		}
	}