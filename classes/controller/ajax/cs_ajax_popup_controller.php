<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_popup_controller extends cs_ajax_controller {
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
			
			global $c_smarty;
			if($c_smarty === true) {
				$this->assignTemplateVariables();
				
				ob_start();
				$this->displayTemplate();
				echo json_encode(ob_get_clean());
			} else {
				echo json_encode('smarty not enabled');
			}
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
		
		private function assignTemplateVariables() {
			
		}
	}