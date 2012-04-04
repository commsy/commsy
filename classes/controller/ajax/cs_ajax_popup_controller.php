<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_popup_controller extends cs_ajax_controller {
		private $_popup_controller = null;
		private $_item_id = null;
		private $_item = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actiongetHTML() {
			// get module from submitted data
			$module = $this->_data['module'];
			$this->_tpl_file = 'popups/' . $module . '_popup';
			
			// item id
			$this->_item_id = $this->_data['iid'];
			$this->assign('popup', 'item_id', $this->_item_id);
			
			// item
			if($this->_item_id !== null && $this->_item_id !== 'NEW') {
				$item_manager = $this->_environment->getItemManager();
				$type = $item_manager->getItemType($this->_item_id);
				if($type === CS_LABEL_TYPE) {
					$label_manager = $this->_environment->getLabelManager();
					$type = $label_manager->getItem($this->_item_id)->getLabelType();
				}
				$manager = $this->_environment->getManager($type);
				$this->_item = $manager->getItem($this->_item_id);
			}
			
			// new / edit
			if($this->_item === null) {
				$this->assign('popup', 'edit', false);
			} else {
				$this->assign('popup', 'edit', true);
			}
			
			// include
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			$class_name = 'cs_popup_' . $module . '_controller';
			$this->_popup_controller = new $class_name($this->_environment, $this);
			
			$this->_popup_controller->assignTemplateVars();
			
			// set Buzzword Information
			if($this->showBuzzwords() === true) {
				$this->assign('popup', 'buzzwords', $this->getBuzzwords());
			}
			
			// set Tag Information
			if($this->showTags() === true) {
				$this->assign('popup', 'tags', $this->getTags());
			}
			
			global $c_smarty;
			if($c_smarty === true) {
				ob_start();
				
				if($this->_item !== null) {
					$this->_popup_controller->edit($this->_item_id);
				}
				
				$this->displayTemplate();
				
				echo json_encode(ob_get_clean());
			} else {
				echo json_encode('smarty not enabled');
			}
		}
		
		public function actionCreate() {	
			// include
			$module = $this->_data['module'];
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			$class_name = 'cs_popup_' . $module . '_controller';
			$this->_popup_controller = new $class_name($this->_environment, $this);
			
			// get form data
			$form_data = array();
			foreach($this->_data['form_data'] as $data) {
				$form_data[$data['name']] = $data['value'];
			}
			
			$this->_popup_controller->create($form_data);
			
			$return = $this->_popup_controller->getReturn();

			echo json_encode($return);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();

		}
		
		public function assign($categorie, $key, $assignment) {
			parent::assign($categorie, $key, $assignment);
		}
		
		public function checkFormData() {
			try {
				$this->checkForm();
				
				return true;
			} catch(cs_form_mandatory_exception $e) {
				echo json_encode('mandatory missing');
				exit;
				
				return false;
			} catch(cs_form_value_exception $e) {
				// TODO: implement in edit form
				echo "value catched";
				
				return false;
			}
		}
		
		private function checkForm() {
			// get form data
			$form_data = array();
			foreach($this->_data['form_data'] as $data) {
				$form_data[$data['name']] = $data['value'];
			}
			
			foreach($this->_popup_controller->getFieldInformation() as $field) {
				// check mandatory
				if(isset($field['mandatory']) && $field['mandatory'] === true) {
					if(!isset($form_data[$field['name']]) || trim($form_data[$field['name']]) === '') {
						throw new cs_form_mandatory_exception('missing mandatory field');
					}
				}
				
				// check values
				// TODO:
				//throw new cs_form_value_exception('value exception');
			}
		}
		
		// TODO:
		// copy from room_controller
		private function showBuzzwords() {
			$context_item = $this->_environment->getCurrentContextItem();
			if($context_item->withBuzzwords() &&
				(	$this->_environment->getCurrentModule() === CS_ANNOUNCEMENT_TYPE ||
					$this->_environment->getCurrentModule() === 'home' ||
					$this->_environment->getCurrentModule() === CS_DATE_TYPE ||
					$this->_environment->getCurrentModule() === CS_MATERIAL_TYPE ||
					$this->_environment->getCurrentModule() === CS_DISCUSSION_TYPE ||
					$this->_environment->getCurrentModule() === CS_TODO_TYPE)) {
				return true;
			}

			return false;
		}
		
		// TODO:
		// copy from room_controller
		private function showTags() {
			$context_item = $this->_environment->getCurrentContextItem();
			if($context_item->withTags() &&
				( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
	                || $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
	                || $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
	                || $this->_environment->getCurrentModule() == CS_TODO_TYPE
	                || $this->_environment->getCurrentModule() == CS_DATE_TYPE
	                || $this->_environment->getCurrentModule() == 'campus_search'
	                || $this->_environment->getCurrentModule() === 'home')) {
				return true;
			}

			return false;
		}
		
		private function getBuzzwords() {
			$return = array();

			$buzzword_manager = $this->_environment->getLabelManager();
			$text_converter = $this->_environment->getTextConverter();
			
			$item_id_array = array();
			if($this->_item !== null) {
				$item_buzzword_list = $this->_item->getBuzzwordList();
				
				$buzzword = $item_buzzword_list->getFirst();
				while($buzzword) {

					$item_id_array[] = $buzzword->getItemID();
					$buzzword = $item_buzzword_list->getNext();
				}
			}
			
			$buzzword_manager->resetLimits();
			$buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
			$buzzword_manager->setTypeLimit('buzzword');
			$buzzword_manager->setGetCountLinks();
			$buzzword_manager->select();
			$buzzword_list = $buzzword_manager->get();

			$buzzword = $buzzword_list->getFirst();
			while($buzzword) {
				$count = $buzzword->getCountLinks();
				if($count > 0) {
					$return[] = array(
						'item_id'			=> $buzzword->getItemID(),
						'name'				=> $text_converter->text_as_html_short($buzzword->getName()),
						'assigned'			=> in_array($buzzword->getItemID(), $item_id_array)
					);
				}

				$buzzword = $buzzword_list->getNext();
			}

			return $return;
		}
		
		private function getTags() {
			$tag_manager = $this->_environment->getTagManager();
			$root_item = $tag_manager->getRootTagItem();

			return $this->buildTagArray($root_item);
		}

		/**
		 * this method goes through the tree structure and generates a nested array of information
		 * @param cs_tag_item $item
		 */
		private function buildTagArray(cs_tag_item $item) {
			$return = array();

			if(isset($item)) {
				$children_list = $item->getChildrenList();

				$item = $children_list->getFirst();
				while($item) {
					// attach to return
					$return[] = array(
						'title'		=> $item->getTitle(),
						'item_id'	=> $item->getItemID(),
						'children'	=> $this->buildTagArray($item)
					);

					$item = $children_list->getNext();
				}
			}

			return $return;
		}
	}