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
			
			// process template for this module popup
			$html = $this->processTemplate($module);
			
			echo json_encode($html);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
		
		private function processTemplate($module) {
			return '<div>test</div>';
		}
	}