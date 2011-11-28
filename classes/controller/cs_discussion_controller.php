<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_discussion_controller extends cs_list_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'room_list';
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
		}
		
		/**
		 * 
		 */
		public function actionIndex() {
			
		}
	}