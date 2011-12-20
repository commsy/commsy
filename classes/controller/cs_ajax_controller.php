<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	abstract class cs_ajax_controller {
		protected $_environment = null;
		protected $_tpl_engine = null;
		protected $_tpl_file = null;
		protected $_tpl_path = null;
		protected $_data = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			$this->_environment = $environment;
			$this->_tpl_engine  = $this->_environment->getTemplateEngine();
			$this->_tpl_file = null;
			$this->_tpl_path = substr($this->_tpl_engine->getTemplateDir(0), 7);
			
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