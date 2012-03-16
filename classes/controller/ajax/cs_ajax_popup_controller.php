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
				ob_start();
				$this->displayTemplate();
				echo json_encode(ob_get_clean());
			} else {
				echo json_encode('smarty not enabled');
			}
		}
		
		public function actionCreate() {
			// get module
			$module = $this->_data['module'];
			
			// get form data
			$form_data = $this->_data['form_data'];
			
			// include
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			
			// get instance
			$popup_controller = new cs_popup_discussion_controller($this->_environment);
			$popup_controller->create($form_data);
			
			$return = $popup_controller->getReturn();

			echo json_encode($return);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}