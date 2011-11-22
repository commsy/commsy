<?php
	abstract class cs_base_controller {
		protected $_environment = null;
		protected $_tpl_engine = null;
		protected $_tpl_file = null;
		
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			$this->_environment = $environment;
			$this->_tpl_engine  = $this->_environment->getTemplateEngine();
			$this->_tpl_file = null;
			
			// process basic template information
			$this->processBaseTemplate();
		}
		
		public function displayTemplate() {
			try {
				$this->_tpl_engine->display($this->_tpl_file, $this->_environment->getOutputMode());
			} catch(Exception $e) {
				die($e->getMessage());
			}
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		abstract protected function processTemplate();
		
		/**
		 * process basic template information
		 */
		private function processBaseTemplate() {
			$tpl_dir = $this->_tpl_engine->getTemplateDir();
			$current_user = $this->_environment->getCurrentUser();
			
			$assign= array();
			$assign['basic']['tpl_path'] = substr($tpl_dir[0], 6);
			$assign['environment']['cid'] = $this->_environment->getCurrentContextID();
			$assign['environment']['username'] = $current_user->getFullName();
			$assign['environment']['is_guest'] = $current_user->isReallyGuest();
			$assign['environment']['is_moderator'] = $current_user->isModerator();
			$this->_tpl_engine->assign($assign);
		}
	}