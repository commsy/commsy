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
		protected function processTemplate() {
			// the actual function determes the method to call
			$function = 'action' . ucfirst($this->_environment->getCurrentFunction());

			if(!method_exists($this, $function)) die('Method ' . $function . ' does not exists!');

			// call
			call_user_func_array(array($this, $function), array());
		}

		/**
		 * assigns a new template variable
		 *
		 * @param $categorie
		 * @param $key
		 * @param mixed $assignment
		 */
		protected function assign($categorie, $key, $assignment) {
			if(!is_string($categorie) || !is_string($key)) die('categorie and key need to be of type string');

			$categorie_vars = $this->_tpl_engine->getTemplateVars($categorie);

			if(isset($categorie_vars) && isset($categorie_vars[$key])) {
				die('this template variable "' . $key . '" in categorie "' . $categorie . '" is already set');
			}

			if(isset($categorie_vars) && !isset($categorie_vars[$key])) {
				$this->_tpl_engine->append($categorie, array($key => $assignment), true);
			} else {
				$assign = array();
				$assign[$categorie][$key] = $assignment;
				$this->_tpl_engine->assign($assign);
			}
		}

		/**
		 * process basic template information
		 */
		private function processBaseTemplate() {
			$tpl_dir = $this->_tpl_engine->getTemplateDir(0);

			$current_user = $this->_environment->getCurrentUser();

			$this->assign('basic', 'tpl_path', substr($tpl_dir, 7));
			$this->assign('environment', 'cid', $this->_environment->getCurrentContextID());
			$this->assign('environment', 'function', $this->_environment->getCurrentFunction());
			$this->assign('environment', 'module', $this->_environment->getCurrentModule());
			$this->assign('environment', 'params', $this->_environment->getCurrentparameterString());
			$this->assign('environment', 'username', $current_user->getFullName());
			$this->assign('environment', 'is_guest', $current_user->isReallyGuest());
			$this->assign('environment', 'is_moderator', $current_user->isModerator());
			$this->assign('translation', 'act_month_long', getLongMonthName(date("n") - 1));
		}
	}