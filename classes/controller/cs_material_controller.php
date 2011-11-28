<?php
	require_once('classes/controller/cs_list_controller.php');
	
	class cs_material_controller extends cs_list_controller {
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
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_MATERIAL_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		
		/**
		 * INDEX
		 */
		public function actionIndex() {
			// get list content
			$list_content = $this->getListContent(array(CS_MATERIAL_TYPE));
			
			$this->assign('room', 'list_content', $list_content[CS_MATERIAL_TYPE]);
		}
	}