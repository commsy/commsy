<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_home_controller extends cs_list_controller {
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
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		
		public function actionIndex() {
			$this->assign('room', 'home_content', $this->getContentForHomeList());
		}
		
		/**
		* gets information for displaying the content in home rubric
		*/
		private function getContentForHomeList() {
			$rubrics = $this->getRubrics();
			$rubric_list = array();
			
			// determe rubrics to show on home list
			foreach($rubrics as $rubric) {
				list($rubric_name, $postfix) = explode('_', $rubric);
		
				// continue if postfix is none or nodisplay
				if($postfix === 'none' || $postfix === 'nodisplay') continue;
		
				// TODO: where does activity come from?
				// continue if name of rubric is activity
				if($rubric_name === 'activity') continue;
				
				$rubric_list[] = $rubric_name;
			}
			
			// get list information
			return $this->getListContent($rubric_list, CS_HOME_RUBRIC_LIST_LIMIT);
		}
	}