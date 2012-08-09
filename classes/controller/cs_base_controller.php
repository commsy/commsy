<?php
	require_once('classes/controller/cs_utils_controller.php');

	abstract class cs_base_controller {
		protected $_environment = null;
		protected $_tpl_engine = null;
		protected $_tpl_file = null;
		protected $_tpl_path = null;
		protected $_utils = null;
		protected $_toJSMixin = null;

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

		public function setTemplateEngine($tplEngine) {
			$this->_tpl_engine = $tplEngine;
		}

		public function displayTemplate() {
			try {
				if($this->_environment->getOutputMode() === 'html') {
					$this->_tpl_engine->setPostToken(true);
				}

				$this->_tpl_engine->display_output($this->_tpl_file, $this->_environment->getOutputMode());
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
			if(!is_string($categorie) || !is_string($key)) die('assign error: category and key need to be of type string');

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

		private function getUseProblems(){
			$current_context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();
			$return_array = array();
			$return_array['show'] = false;
			$session_item = $this->_environment->getSessionItem();
      		$return_array['content'] = '';
      		$return_array['browser'] = false;
			$return_array['big_problem'] = false;
			$return_array['problem'] = false;
      		if($session_item->issetValue('javascript')){
         		if($session_item->getValue('javascript') == "-1"){
            		$return_array['content'] .= ' '.$translator->getMessage('COMMON_NO_JAVASCRIPT_POSSIBLE');
					$return_array['show'] = true;
					$return_array['big_problem'] = true;
         		}
      		}
/*      		if($session_item->issetValue('flash')){
         		if($session_item->getValue('flash') == "-1"){
            		$return_array['content'] .= $translator->getMessage('COMMON_NO_FLASH_POSSIBLE');
					$return_array['show'] = true;
					$return_array['big_problem'] = true;
         		}
      		}
*/
        	$current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
        	$current_browser_version = $this->_environment->getCurrentBrowserVersion();
        	if ($current_browser == 'msie'
        		and (strstr($current_browser_version,'7.') or strstr($current_browser_version,'6.'))
        		){
				$return_array['problem'] = true;
				$return_array['show'] = true;
            	$return_array['content'] .= ' '.$translator->getMessage('COMMON_NO_IE_LOWER_THEN_8');
        	}
        	if ($current_browser == 'opera'){
				$return_array['problem'] = true;
				$return_array['show'] = true;
            	$return_array['content'] .= ' '.$translator->getMessage('COMMON_NO_OPERA');
        	}
        	if ( empty($_COOKIE) ) {
				$return_array['problem'] = true;
				$return_array['show'] = true;
            	$return_array['content'] .= ' '.$translator->getMessage('COMMON_NO_COOKIE');
   			}

        	return $return_array;

		}

		private function _getServiceMailLink(){
			$current_context = $this->_environment->getCurrentContextItem();
            $service_link_ext = $current_context->getServiceLinkExternal();
            $current_user = $this->_environment->getCurrentUserItem();
            $translator = $this->_environment->getTranslationObject();
			$email_to_service = '';

            if ($service_link_ext == '') {
               $portal_item = $this->_environment->getCurrentPortalItem();
               if (isset($portal_item) and !empty($portal_item)) {
                  $service_link_ext = $portal_item->getServiceLinkExternal();
               }
               unset($portal_item);
            }

            if ($service_link_ext == '') {
               $server_item = $this->_environment->getServerItem();
               $service_link_ext = $server_item->getServiceLinkExternal();
            }

            if ( !empty($service_link_ext) ) {
               if ( strstr($service_link_ext,'%') ) {
                  $text_convert = $this->_environment->getTextConverter();
                  $service_link_ext = $text_convert->convertPercent($service_link_ext,false,true);
               }
               $email_to_service = '<a href="'.$service_link_ext.'" title="'.$translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE').'" target="_blank">'.$translator->getMessage('COMMON_MAIL_TO_SERVICE2').'</a>';
            } else {
            // exernal link: END

               $server_item = $this->_environment->getServerItem();
               $link = 'http://www.commsy.net/?n=Software.FAQ&amp;mod=edit';

               //Hierarchy of service-email: Set email, test if portal tier has one, then server tier
               $service_email = $current_context->getServiceEmail();

               if ($service_email == '') {
                  $portal_item = $this->_environment->getCurrentPortalItem();
                  if (isset($portal_item) and !empty($portal_item)) {
                     $service_email = $portal_item->getServiceEmail();
                  }
                  unset($portal_item);
               }

               if ($service_email == '') {
                  $service_email = $server_item->getServiceEmail();
               }

              if ($service_email == '') {
                  $service_email = 'NONE';
               }

               $ip = 'unknown';
               if ( !empty($_SERVER["SERVER_ADDR"]) ) {
                  $ip = $_SERVER["SERVER_ADDR"];
               } elseif ( !empty($_SERVER["HTTP_HOST"]) ) {
                  $ip = $_SERVER["HTTP_HOST"];
               }

               $email_to_service = '<form id="supportForm" action="'.$link.'" method="post" name="service" style="margin-bottom: 0px;">'.LF;
               $email_to_service .= '<input type="hidden" name="server_name" value="'.$server_item->getTitle().'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="server_ip" value="'.$ip.'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="context_id" value="'.$current_context->getItemID().'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="context_name" value="'.$current_context->getTitle().'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="context_type" value="'.$current_context->getType().'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="user_name" value="'.$current_user->getFullname().'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="user_email" value="'.$current_user->getEmail().'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="service_email" value="'.$service_email.'"/>'.LF;
               $email_to_service .= '<a href="#" title="'.$translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE').'" onClick="document.getElementById(\'supportForm\').submit();">'.$translator->getMessage('COMMON_MAIL_TO_SERVICE2').'</a>'.LF;
               // jQuery
               $email_to_service .= '</form>'.LF;
 			}
 			return $email_to_service;
		}

		/**
		 * process basic template information
		 */
		private function processBaseTemplate() {
			$current_user = $this->_environment->getCurrentUser();
			$own_room_item = $current_user->getOwnRoom();
			$portal_user = $current_user->getRelatedPortalUserItem();
			$portal_item = $this->_environment->getCurrentPortalItem();
			$current_context = $this->_environment->getCurrentContextItem();
			$translator = $this->_environment->getTranslationObject();
			$count_new_accounts = 0;
			if ($current_user->isModerator()){
				// tasks
		        $manager = $this->_environment->getTaskManager();
		        $manager->resetLimits();
		        $manager->setContextLimit($this->_environment->getCurrentContextID());
		        $manager->setStatusLimit('REQUEST');
		        $manager->select();
		        $tasks = $manager->get();
		        $task = $tasks->getFirst();
		        $show_user_config = false;
		        $count_new_accounts = 0;
		        while($task){
		           $mode = $task->getTitle();
		           $task = $tasks->getNext();
		           if ($mode == 'TASK_USER_REQUEST'){
		              $count_new_accounts ++;
		              $show_user_config = true;
		           }
		        }

			}

			global $c_jsmath_enable;
			global $c_jsmath_url;
			if (!isset($c_jsmath_enable)){
				$c_jsmath_enable = false;
			}
			if (!isset($c_jsmath_url)){
				$c_jsmath_url = '';
			}
			$this->assign('basic', 'tpl_path', $this->_tpl_path);
			$this->assign('environment', 'cid', $this->_environment->getCurrentContextID());
			$this->assign('environment', 'pid', $this->_environment->getCurrentPortalID());
			$this->assign('environment', 'function', $this->_environment->getCurrentFunction());
			$this->assign('environment', 'module', $this->_environment->getCurrentModule());
			$this->assign('environment', 'module_name', $translator->getMessage(strtoupper($this->_environment->getCurrentModule())).'_INDEX');
			$this->assign('environment', 'params', $this->_environment->getCurrentParameterString());
			$this->assign('environment', 'params_array', $this->_environment->getCurrentParameterArray());
			$this->assign('environment', 'username', $current_user->getFullName());
			$this->assign('environment', 'user_item_id', $current_user->getItemID());
			$this->assign('environment', 'user_picture', $current_user->getPicture());
			$this->assign('environment', 'room_type_commnunity', $current_context->isCommunityRoom());
			$this->assign('environment', 'room_type_group', $current_context->isGroupRoom());
			$this->assign('environment', 'is_guest', $current_user->isReallyGuest());
			$this->assign('environment', 'is_moderator', $current_user->isModerator());
			$this->assign('translation', 'act_month_long', getLongMonthName(date("n") - 1));
			$this->assign('environment', 'lang', $this->_environment->getSelectedLanguage());
			$this->assign('environment', 'logo', $current_context->getLogoFileName());
			$this->assign('environment', 'room_title', $current_context->getTitle());
			$this->assign('environment', 'portal_title', $portal_item->getTitle());
			$this->assign('environment', 'show_room_title', $current_context->showTitle());
			$this->assign('environment', 'language', $current_context->getLanguage());
			$this->assign('environment','count_copies', $this->getUtils()->getCopyCount());
			$this->assign('environment','show_moderator_link', $current_context->showMail2ModeratorLink());
			$this->assign('environment','show_service_link', $portal_item->showServiceLink());
			$this->assign('environment','service_link', $this->_getServiceMailLink());
			$this->assign('environment','c_jsmath_enable', $c_jsmath_enable);
			$this->assign('environment','c_jsmath_url', $c_jsmath_url);
			$this->assign('environment','count_new_accounts', $count_new_accounts);
			$this->assign('environment', 'post', $_POST);
			$this->assign('environment', 'get', $_GET);
			
			// browser
			$current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
			$current_browser_version = $this->_environment->getCurrentBrowserVersion();
			
			$IE8 = false;
			if ($current_browser == 'msie' && strstr($current_browser_version,'8.')) {
				$IE8 = true;
			}
			$this->assign("environment", "IE8", $IE8);

			$ownRoomItem = $current_user->getOwnRoom();

			if ($ownRoomItem) {
				$this->assign("own", "id", $ownRoomItem->getItemId());
				$this->assign("own", "with_activating", $ownRoomItem->withActivatingContent());
			}

			$this->assign('environment', 'use_problems', $this->getUseProblems());


			if (isset($own_room_item)){
				$this->assign('cs_bar', 'show_widgets', $own_room_item->getCSBarShowWidgets());
				$this->assign('cs_bar', 'show_calendar', $own_room_item->getCSBarShowCalendar());
				$this->assign('cs_bar', 'show_stack', $own_room_item->getCSBarShowStack());
			}else{
				$this->assign('cs_bar', 'show_widgets', false);
				$this->assign('cs_bar', 'show_calendar', false);
				$this->assign('cs_bar', 'show_stack', false);
			}



			// to javascript
			$to_javascript = array();

			$to_javascript['template']['tpl_path'] = $this->_tpl_path;
			$to_javascript['environment']['lang'] = $this->_environment->getSelectedLanguage();
			$to_javascript['environment']['single_entry_point'] = $this->_environment->getConfiguration('c_single_entry_point');
			$to_javascript['environment']['max_upload_size'] = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
			$to_javascript['i18n']['COMMON_NEW_BLOCK'] = $translator->getMessage('COMMON_NEW_BLOCK');
			$to_javascript['i18n']['COMMON_SAVE_BUTTON'] = $translator->getMessage('COMMON_SAVE_BUTTON');
			$to_javascript['security']['token'] = getToken();
			$to_javascript['autosave']['mode'] = 0;
			$to_javascript['autosave']['limit'] = 0;

			if ($ownRoomItem) {
				$to_javascript['ownRoom']['id'] = $ownRoomItem->getItemId();
			}

			// translations - should be managed elsewhere soon
			$to_javascript["translations"]["common_hide"] = $translator->getMessage("COMMON_HIDE");
			$to_javascript["translations"]["common_show"] = $translator->getMessage("COMMON_SHOW");

			// dev
			global $c_indexed_search;
			$this->assign('environment','with_indexed_search',(isset($c_indexed_search) && $c_indexed_search === true) ? true : false);
			$to_javascript['dev']['indexed_search'] = (isset($c_indexed_search) && $c_indexed_search === true) ? true : false;

			if(isset($portal_user) && $portal_user->isAutoSaveOn()) {
				global $c_autosave_mode;
				global $c_autosave_limit;

				if(isset($c_autosave_mode) && isset($c_autosave_limit)) {
					$to_javascript['autosave']['mode'] = $c_autosave_mode;
					$to_javascript['autosave']['limit'] = $c_autosave_limit;
				}
			}

			// mixin javascript variables
			if(is_array($this->_toJSMixin)) {
				$to_javascript = array_merge($to_javascript, $this->_toJSMixin);
			}

			$this->assign('javascript', 'variables_as_json', json_encode($to_javascript));
		}
	}