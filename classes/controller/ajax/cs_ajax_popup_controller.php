<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_popup_controller extends cs_ajax_controller {
		private $_popup_controller = null;
		private $_item_id = null;
		
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
			$this->assign('popup', 'item_id', $this->_data['iid']);
			
			// include
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			$class_name = 'cs_popup_' . $module . '_controller';
			$this->_popup_controller = new $class_name($this->_environment, $this);
			
			$this->_popup_controller->assignTemplateVars();
			
			// set Buzzword Information
			if($this->showBuzzwords() === true) {
				$this->assign('popup', 'buzzwords', $this->getBuzzwords());
			}
			
			global $c_smarty;
			if($c_smarty === true) {
				ob_start();
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
		
		protected function getBuzzwords() {
			$return = array();

			$buzzword_manager = $this->_environment->getLabelManager();
			$text_converter = $this->_environment->getTextConverter();
      		$params = $this->_environment->getCurrentParameterArray();

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
						'name'				=> $text_converter->text_as_html_short($buzzword->getName())
					);
				}

				$buzzword = $buzzword_list->getNext();
			}

			return $return;
		}
	}