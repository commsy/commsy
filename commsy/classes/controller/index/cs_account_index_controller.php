<?php
	/************************************************************************************
	 * This is only for backward compatibility with CommSy7
	 * and is used to automatically open the account tab in room configuration popup
	 * 
	 * What we need to do is:
	 * - open room config popup automatically
	 * - switch to accounts tab
	 * - perform a request with preselected status
	************************************************************************************/

	require_once('classes/controller/cs_home_controller.php');
	
	class cs_account_index_controller extends cs_home_controller {
		public function __construct(cs_environment $environment) {
			$this->_toJSMixin = array("autoOpenPopup" => array(
				"popup"			=> "tm_settings",
				"tab"			=> "accounts",
				"parameters"	=> array("filter" => "1")
			));
			
			// call parent
			parent::__construct($environment);
			
			$this->_environment->setCurrentModule("home");
		}
	}