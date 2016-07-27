<?php
	require_once('classes/controller/cs_edit_controller.php');

	class cs_discussion_edit_controller extends cs_edit_controller {
		private $_item = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'discussion_edit';
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
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionEdit() {
			//$this->assign('detail', 'content', $this->getDetailContent());
			
			$session = $this->_environment->getSessionItem();
			
			// get post data
			$this->getPostData();
			
			if($this->_item_id !== null) {
				$discarticle_manager = $this->_environment->getDiscussionArticlesManager();
				$this->_item = $discarticle_manager->getItem($this->_item_id);
			}
			
			
			// find the discussion this article belongs to
			if($this->_item !== NULL) {
				$discussion_id = $this->_item->getDiscussionID();
				$ref_position = $this->_item->getPosition();
			} else {
				if(!empty($_GET['did'])) {
					$discussion_id = $_GET['did'];
				} elseif(!empty($_POST['discussion_id'])) {
					$discussion_id = $_POST['discussion_id'];
				} elseif(!empty($_GET['discussion_id'])) {
					$discussion_id = $_GET['discussion_id'];
				} else {
				
				}
				
			}
			
				
			$translator = $this->_environment->getTranslationObject();
			
			// cancel editing
			if(isOption($this->_command, $translator->getMessage('COMMON_CANCEL_BUTTON'))) {
				
			// show form and/or save item
			} else {
				if(	isset($_GET['back_to_discussion_detail_view']) &&
				//TODO: command is not defined, use command from parent class($this->_command)
					!empty($command) &&
						!(isOption($command, $translator->getMessage('DISCARTICLE_SAVE_BUTTON')) ||
						isOption($command, $translator->getMessage('DISCARTICLE_CHANGE_BUTTON')))) {	
				
			
					$post_file_ids = array();
					if(isset($_POST['filelist'])) {
						$post_file_ids = $_POST['filelist'];
					}
					
					// set session post vars
					$session_post_vars = $_POST;
					if(isset($post_file_ids) && !empty($post_file_ids)) {
						$session_post_vars['filelist'] = $post_file_ids;
					}
					
					$session_item->setValue('back_to_discussion_detail_view_postvars', $session_post_vars);
					
					if(isset($discarticle_item)) {
						$session_item->setValue('back_to_discussion_detail_view_last_upload', 'edit' . $discarticle_item->getItemID());
					} else {
						$session_item->setValue('back_to_discussion_detail_view_last_upload', 'new' . $_GET['answer_to']);
					}
				
			
				
				
				}
				
				$post_file_ids = array();
				if(isset($_POST['filelist'])) {
					$post_file_ids = $_POST['filelist'];
				}

				// load form data from postvars
				if(!empty($_POST)) {
					$session_post_vars = $_POST;
					if(isset($post_file_ids) && !empty($post_file_ids)) {
						$session_post_vars['filelist'] = $post_file_ids;
					}
					//$form->setFormPost($session_post_vars);
				}
				
				// load form data from database
				elseif(isset($this->_item)) {
					
				}
				
				// create data for new item
				elseif($this->_item_id === null) {
					$this->cleanup_session($this->_item_id);
				} else {
					include_once('functions/error_functions.php');trigger_error('discarticle_edit was called in an unknown manner', E_USER_ERROR);
				}
				
				if($session->issetValue($this->_environment->getCurrentModule() . '_add_files')) {
					//$form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
					//die("UPLOAD");
				}
				
				// save item
				if($this->_command === 'new') {
					// TODO: implement form check
					$correct = true;
					if($correct) {
						// create new item
						if(!isset($this->_item)) {
							$discarticle_manager = $this->_environment->getDiscussionArticlesManager();
							$discarticle_item = $discarticle_manager->getNewItem();
							$discarticle_item->setContextID($this->_environment->getCurrentContextID());
							$user = $this->_environment->getCurrentUserItem();
							$discarticle_item->setCreatorItem($user);
							$discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
							$discarticle_item->setDiscussionID($discussion_id);
							
							$discussion_manager = $this->_environment->getDiscussionManager();
							$discussion_item = $discussion_manager->getItem($discussion_id);
							$discussion_type = $discussion_item->getDiscussionType();
							if($discussion_type === 'threaded') {
								
							} else {
								$discarticle_item->setPosition('1');
							}
						}
						
						// set modificator and modification date
						$user = $this->_environment->getCurrentUserItem();
						$discarticle_item->setModificatorItem($user);
						$discarticle_item->setModificationDate(getCurrentDateTimeInMySQL());
						
						// set attributes
						if(isset($_POST['form_data']['title'])) {
							$discarticle_item->setSubject($_POST['form_data']['title']);
						}
						if(isset($_POST['form_data']['description'])) {
							$discarticle_item->setDescription($_POST['form_data']['description']);
						}
						
						// set links to connected rubrics
						if(isset($_POST[CS_MATERIAL_TYPE])) {
							$discarticle_item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
						} else {
							$discarticle_item->setMaterialListByID(array());
						}
						
						$this->setFilesForItem($discarticle_item, $post_file_ids);
						
						// save item
						$discarticle_item->save();
						
						// redirect
						$this->cleanup_session($this->_item_id);
						$params = array();
						$params['iid'] = $discarticle_item->getDiscussionID();
						redirect($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, 'disc_article_' . $discarticle_item->getItemID());
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
		}
	}