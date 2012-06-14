<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_tagtree_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetTreeData() {
			$utils = $this->getUtils();
			if($utils->showTags()) {
				$tags = $utils->getTags();
			}
			
			$this->setSuccessfullDataReturn($tags);
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}
?>