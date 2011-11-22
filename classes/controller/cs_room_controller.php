<?php
	require_once('classes/controller/cs_base_controller.php');
	
	abstract class cs_room_controller extends cs_base_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// check room context
			if(	!$this->_environment->inProjectRoom() &&
				!$this->_environment->inCommunityRoom() &&
				!$this->_environment->inPrivateRoom() &&
				!$this->environment->inGroupRoom()) {
				die('you are not in room context, so no room template should be processed');	
			}
			
			$this->_tpl_engine->assign('room', 'rubric_information', $this->getRubricInformation());
		}
		
		/**
		 * 
		 * gets information for displaying room rubrics
		 */
		private function getRubricInformation() {
			$return = array();
			$rubric_configuration = $this->_environment->getCurrentContextItem()->getHomeConf();
			
			$rubrics = array();
			if(!empty($rubric_configuration)) {
				$rubrics = explode(',', $rubric_configuration);
			}
			
			foreach($rubrics as $rubric) {
				list($suffix, $postfix) = explode('_', $rubric);
				
				if($postfix !== 'none') {
					$name = '';
					$translate = true;
					if($this->_environment->isPlugin($suffix)) {
						$name = plugin_hook_output($suffix, 'getDisplayName');
						$translate = false;
					} else {
						$name = $suffix;
					}
					
					if(empty($name)) die('rubric name could not be found');
					
					$return[] = array(	'name'		=> $name,
										'translate'	=> $translate,
										'is_new'	=> false);
				}
			}
		}
	}