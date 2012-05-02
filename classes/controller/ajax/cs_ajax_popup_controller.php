<?php
	require_once('classes/controller/cs_ajax_controller.php');
	require_once('classes/controller/cs_room_controller.php');
	
	class cs_ajax_popup_controller extends cs_ajax_controller {
		protected $_popup_controller = null;
		
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
			
			// include
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			$class_name = 'cs_popup_' . $module . '_controller';
			$this->_popup_controller = new $class_name($this->_environment, $this);
			
			// initPopup
			$this->initPopup();
			
			global $c_smarty;
			if($c_smarty === true) {
				ob_start();
				
				$this->displayTemplate();
				
				$output = json_encode(ob_get_clean());
				echo $output;
				// TODO: optimize
				//echo str_replace(array('\n', '\t'), '', $output);
			} else {
				echo json_encode('smarty not enabled');
			}
		}
		
		protected function initPopup() {
			$this->_popup_controller->initPopup();
		}
		
		public function actionSave() {
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
			
			// additional data
			$additional = array();
			if(isset($this->_data['additional']) && !empty($this->_data['additional'])) {
				$additional = $this->_data['additional'];
			}
			
			$this->_popup_controller->save($form_data, $additional);
			
			$return = $this->_popup_controller->getReturn();

			echo json_encode($return);
		}
		
		public function checkFormData($sub = '') {
			try {
				$this->checkForm($sub);
		
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
		
		private function checkForm($sub) {
			// get form data
			$form_data = array();
			foreach($this->_data['form_data'] as $data) {
				$form_data[$data['name']] = $data['value'];
			}
			
			foreach($this->_popup_controller->getFieldInformation($sub) as $field) {
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
		
		public function getUtils() {
			return parent::getUtils();
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
	}