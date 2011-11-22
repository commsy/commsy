<?php
	require_once('classes/controller/cs_custom_room_controller.php');
	
	class cs_home_controller extends cs_custom_room_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'room_home';
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}
	}