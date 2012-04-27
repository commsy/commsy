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
			
			$this->_popup_controller->save($form_data);
			
			$return = $this->_popup_controller->getReturn();

			echo json_encode($return);
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