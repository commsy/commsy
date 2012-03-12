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
			
			global $c_smarty;
			if($c_smarty === true) {
				// process template for this module popup
				$html = $this->processTemplate($module);
				
				echo json_encode($html);
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
		
		private function processTemplate($module) {
			$controller_name = 'cs_' . $module . '_edit_controller';
			require_once('classes/controller/edit/' . $controller_name . '.php');

			$controller = new $controller_name($this->_environment);
			
			$controller->processTemplate();
			
			ob_start();
			$controller->displayTemplate();
			return ob_get_clean();
		}
	}