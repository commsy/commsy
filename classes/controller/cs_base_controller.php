<?php
	require_once('classes/controller/cs_utils_controller.php');

	abstract class cs_base_controller {
		protected $_environment = null;
		protected $_tpl_engine = null;
		protected $_tpl_file = null;
		protected $_tpl_path = null;
		protected $_utils = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			$this->_environment = $environment;
			$this->_tpl_engine  = $this->_environment->getTemplateEngine();
			$this->_tpl_file = null;

			// set correct template path
			if($this->_tpl_engine->getTheme() !== 'default') {
				$this->_tpl_path = substr($this->_tpl_engine->getTemplateDir(1), 7);
			} else {
				$this->_tpl_path = substr($this->_tpl_engine->getTemplateDir(0), 7);
			}

			// process basic template information
			$this->processBaseTemplate();

			// setup error handling
			set_error_handler(array($this, 'errorHandler'));
			set_exception_handler(array($this, 'exceptionHandler'));
			//register_shutdown_function(array($this, 'shutdownHandler'));

			// load exceptions
			require_once('classes/exceptions/cs_form_exceptions.php');
			require_once('classes/exceptions/cs_detail_exceptions.php');
		}

		public function errorHandler($error_code, $error_string, $error_file, $error_line, $error_context) {
			// create an exception
			$exception = new ErrorException($error_string, $error_code, 0, $error_file, $error_line);

			// call exception handler with object
			$this->exceptionHandler($exception);
		}

		/*
		 * this will catch unhandled exceptions and exceptions from error handler
		 */
		public function exceptionHandler($exception) {
			global $c_show_debug_infos;
			if(isset($c_show_debug_infos) && $c_show_debug_infos === true) {
				/*
				echo "an unhandled exception / error occured: </br></br>\n";
				echo "See " . $exception->getFile() . " on line " . $exception->getLine() . "<br>\n";
				pr($exception->getMessage());
				echo "-------------------------<br>\n";
				*/
			}
			//pr($exception);
			//exit;
		}

		/*
		public function shutdownHandler() {
			pr("error");
		}
		*/

		public function displayTemplate() {
			try {
				if($this->_environment->getOutputMode() === 'html') {
					$this->_tpl_engine->setPostToken(true);
				}

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
			try {
				call_user_func_array(array($this, $function), array());
			} catch(cs_detail_item_type_exception $e) {
				// reset template vars
				$e->resetTemplateVars($this->_tpl_engine);

				// set template
				$this->_tpl_file = 'error';

				$this->assign('exception', 'message_tag', $e->getErrorMessageTag());
			}

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

		protected function getUtils() {
			if($this->_utils === null) {
				$this->_utils = new cs_utils_controller($this->_environment);
			}

			return $this->_utils;
		}

		/**
		 * process basic template information
		 */
		private function processBaseTemplate() {
			$current_user = $this->_environment->getCurrentUser();

			$this->assign('basic', 'tpl_path', $this->_tpl_path);
			$this->assign('environment', 'cid', $this->_environment->getCurrentContextID());
			$this->assign('environment', 'function', $this->_environment->getCurrentFunction());
			$this->assign('environment', 'module', $this->_environment->getCurrentModule());
			$this->assign('environment', 'params', $this->_environment->getCurrentparameterString());
			$this->assign('environment', 'username', $current_user->getFullName());
			$this->assign('environment', 'user_picture', $current_user->getPicture());
			$this->assign('environment', 'is_guest', $current_user->isReallyGuest());
			$this->assign('environment', 'is_moderator', $current_user->isModerator());
			$this->assign('translation', 'act_month_long', getLongMonthName(date("n") - 1));
			$this->assign('environment', 'lang', $this->_environment->getSelectedLanguage());
			$this->assign('environment', 'post', $_POST);
			$this->assign('environment', 'get', $_GET);
		}
	}