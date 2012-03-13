<?php
	require_once('classes/controller/cs_base_controller.php');
	
	abstract class cs_ajax_controller extends cs_base_controller {
		protected $_data = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			// deativate debug information
			global $c_show_debug_infos;
			$c_show_debug_infos = false;
			
			// set output mode
			$this->_environment->setOutputMode('JSON');
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function process() {
			// decode json into array
			$this->_data = json_decode(file_get_contents('php://input'), true);
			
			// the actual function determes the method to call
			$function = 'action' . ucfirst($_GET['action']);

			if(!method_exists($this, $function)) die('Method ' . $function . ' does not exists!');

			// call
			call_user_func_array(array($this, $function), array());
		}
	}