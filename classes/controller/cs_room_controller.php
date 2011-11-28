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
		protected function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// check room context
			if(	!$this->_environment->inProjectRoom() &&
				!$this->_environment->inCommunityRoom() &&
				!$this->_environment->inPrivateRoom() &&
				!$this->environment->inGroupRoom()) {
				die('you are not in room context, so no room template should be processed');	
			}
			
			$this->assign('room', 'rubric_information', $this->getRubricInformation());
		}
		
		/**
		 * get all rubrics and their configuration encoded in postfixes
		 */
		protected function getRubrics() {
			// get rubric configuration
			$rubric_configuration = $this->_environment->getCurrentContextItem()->getHomeConf();
			
			$rubrics = array();
			if(!empty($rubric_configuration)) {
				$rubrics = explode(',', $rubric_configuration);
			}
			
			return $rubrics;
		}
		
		/**
		 * gets information for displaying room rubrics in navigation bar
		 */
		private function getRubricInformation() {
			// init return with home
			$return = array();
			$return[] = array(
				'name'			=> 'home',
				'translate'		=> false,
				'active'		=> $this->_environment->getCurrentModule() == 'home',
				'span_prefix'	=> 'ho');
			
			// get rubrics
			$rubrics = $this->getRubrics();
			
			// these prefixes are needed for building up the span id
			$span_lookup = array(
				CS_ANNOUNCEMENT_TYPE	=>	'an',
				CS_DATE_TYPE			=>	'te',
				CS_MATERIAL_TYPE		=>	'ma',
				CS_DISCUSSION_TYPE		=>	'di',
				CS_USER_TYPE			=>	'pe',
				CS_GROUP_TYPE			=>	'gr',
				CS_TODO_TYPE			=>	'au',
				CS_TOPIC_TYPE			=>	'th'
			);
			
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
					
					// append return
					$return[] = array(	'name'			=> $name,
										'translate'		=> $translate,
										'active'		=> $this->_environment->getCurrentModule() == $name,
										'span_prefix'	=> $span_lookup[$name]);
				}
			}
			
			return $return;
		}
	}