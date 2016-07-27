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

            // load exceptions
            require_once('classes/exceptions/cs_form_exceptions.php');
            require_once('classes/exceptions/cs_detail_exceptions.php');
        }

        public function setTemplateEngine($tplEngine) {
            $this->_tpl_engine = $tplEngine;
        }

        public function displayTemplate() {
            try {
                if($this->_environment->getOutputMode() === 'html') {
                    $this->_tpl_engine->setPostToken(true);
                }
                // print - download pdf
                if($this->_environment->getOutputMode() === 'print' && $_GET['mod'] != "download"){

                    $contextItem = $this->_environment->getCurrentContextItem();
                    $roomTitle = str_replace(' ', '_', $contextItem->getTitle());
                    
                    require_once('classes/cs_mpdf.php');
                    $mpdf = new cs_mpdf();

                    // set proxy for mpdf
                    global $symfonyContainer;
                    $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
                    $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');
                    
                    if($c_proxy_port) {
                        $mpdf->proxy = true;
                        $mpdf->proxyUrl = $c_proxy_ip.":".$c_proxy_port;
                    }
                    
                    // debug
                    if($_GET['debug'] == 1){
                        $mpdf->showImageErrors = true;
                    }
                                
                    $image = '<img src="../htdocs/images/commsy_logo_transparent.gif"/>';
                    $mpdf->setHeader($contextItem->getTitle().'| CommSy | {DATE j.m.Y} ');
                    #$mpdf->setHTMLHeader($image);
                    $mpdf->setFooter('|{PAGENO}/{nbpg}');
                    ob_start();
                    $this->_tpl_engine->display_output($this->_tpl_file, $this->_environment->getOutputMode());
                    $output = ob_get_clean();
                    $mpdf->WriteHTML($output);
                    
                    if(!empty($_GET['iid'])){
                        $id = $_GET['iid'];
                        $mpdf->Output($roomTitle.'_'.$id.'.pdf', 'I');
                    } else {
                        $mpdf->Output($roomTitle.'.pdf', 'I');
                    }
                    exit;
                }
                $this->_tpl_engine->display_output($this->_tpl_file, $this->_environment->getOutputMode());
            } catch(Exception $e) {
                die($e->getMessage());
            }
        }

        public function sanitize (&$item, $key){
            #$item = $this->getUtils()->sanitize($item);
        }

        /*
         * every derived class needs to implement an processTemplate function
         */
        protected function processTemplate() {
            $converter = $this->_environment->getTextConverter();

            if(!empty($_GET) and isset($_GET)){
                array_walk_recursive($_GET, array($converter, 'sanitizeHTML'));
            }

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
/*              if($session_item->issetValue('flash')){
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


        private function getAddonInformation() {
            $return = array(
                'wiki'      => array(
                    'active'    => false
                ),
                'wordpress' => array(
                    'active'    => false
                )
            );

            $current_user = $this->_environment->getPortalUserItem();
            $current_context = $current_user->getOwnRoom();
            $current_portal_item = $this->_environment->getCurrentPortalItem();
            $count = 0;

            // wiki
            if($current_context->showWikiLink() && $current_context->existWiki() && $current_context->issetWikiHomeLink()) {
                    global $c_pmwiki_path_url;

                    $count++;
                    $return['wiki']['active'] = true;
                    $return['wiki']['title'] = $current_context->getWikiTitle();
                    $return['wiki']['path'] = $c_pmwiki_path_url;
                    $return['wiki']['portal_id'] = $this->_environment->getCurrentPortalID();
                    $return['wiki']['item_id'] = $current_context->getItemID();

                    $url_session_id = '';
                    if($current_context->withWikiUseCommSyLogin()) {
                        $session_item = $this->_environment->getSessionItem();
                        $url_session_id = '?commsy_session_id=' . $session_item->getSessionID();
                        unset($session_item);
                    }
                    $return['wiki']['session'] = $url_session_id;
            }


            // wordpress
            if($current_context->existWordpress()) {
                $wordpress_path_url = $current_portal_item->getWordpressUrl();
                $count++;
                $return['wordpress']['active'] = true;
                $return['wordpress']['title'] = $current_context->getWordpressTitle();
                $return['wordpress']['path'] = $wordpress_path_url;
                $return['wordpress']['item_id'] = $current_context->getItemID();

                $url_session_id = '';
                if($current_context->withWordpressUseCommSyLogin()) {
                    $session_item = $this->_environment->getSessionItem();
                    $url_session_id = '?commsy_session_id=' . $session_item->getSessionID();
                    unset($session_item);
                }
                $return['wordpress']['session'] = $url_session_id;
            }

            // plugins
            $plugin_array = plugin_hook_output_all('getMyAreaActionAsArray',array(),'MULTIARRAY');
            if ( !empty($plugin_array) ) {
               $plugin_array2['plugins'] = $plugin_array;
               $return = array_merge($return,$plugin_array2);
            }
            return $return;
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
            $portal_user_item = $current_user->getRelatedPortalUserItem();
            if ($portal_user_item) {
               $this->assign('environment', 'user_language', $portal_user_item->getLanguage());
            }
            $count_new_accounts = 0;
            if ($current_user->isModerator()){
                // user count
                $manager = $this->_environment->getUserManager();
                $manager->resetLimits();
                $manager->setContextLimit($this->_environment->getCurrentContextID());
                $manager->setStatusLimit(1);
                $manager->select();
                $user = $manager->get();
                $count_new_accounts = 0;
                if ($user->getCount() > 0) {
                    $count_new_accounts = $user->getCount();
                }
                // // tasks
                // $manager = $this->_environment->getTaskManager();
                // $manager->resetLimits();
                // $manager->setContextLimit($this->_environment->getCurrentContextID());
                // $manager->setStatusLimit('REQUEST');
                // $manager->select();
                // $tasks = $manager->get();
                // $task = $tasks->getFirst();
                // $count_new_accounts = 0;
                // while($task){
                //    $mode = $task->getTitle();
                //    $task = $tasks->getNext();
                //    if ($mode == 'TASK_USER_REQUEST'){
                //       $count_new_accounts ++;
                //    }
                // }

            }

            global $c_jsmath_enable;
            global $c_jsmath_url;
            if (!isset($c_jsmath_enable)){
                $c_jsmath_enable = false;
            }
            if (!isset($c_jsmath_url)){
                $c_jsmath_url = '';
            }

            global $c_js_mode;

            global $c_commsy_url_path;
            global $c_commsy_domain;
            $url_path = '';
            if ($c_commsy_url_path != '') {
               $url_path = $c_commsy_url_path . '/';
               if (!(strpos($url_path, '/') === 0)) {
                  $url_path = '/' . $c_commsy_url_path;
               }
            } else {
               $url_path = '/';
            }
            $this->assign('basic', 'commsy_path', $c_commsy_domain . $url_path);
            $this->assign('basic', 'tpl_path', $c_commsy_domain . $url_path . $this->_tpl_path);
            $this->assign('environment', 'cid', $this->_environment->getCurrentContextID());
            $this->assign('environment', 'pid', $this->_environment->getCurrentPortalID());
            $this->assign('environment', 'current_user_id', $this->_environment->getCurrentUserID());
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
            $this->assign('environment', 'is_read_only', $current_user->isOnlyReadUser());
            $this->assign('environment', 'is_moderator', $current_user->isModerator());
            $this->assign('environment', 'is_root', $current_user->isRoot());
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
            $this->assign('environment','c_js_mode', (isset($c_js_mode) && $c_js_mode === "layer") ? $c_js_mode : "source");
            $this->assign('environment','count_new_accounts', $count_new_accounts);
            $this->assign('environment', 'post', $_POST);
            $this->assign('environment', 'get', $_GET);
            
            $print_params = array();
            foreach ($this->_environment->getCurrentParameterArray() as $key => $value) {
                if ($key != 'mode') {
                    $print_params[$key] = $value;
                }
            }
            $this->assign('print', 'params_array', $print_params);

            include_once('functions/misc_functions.php');
            $this->assign('environment','commsy_version',getCommSyVersion());
            $c_version_addon = $this->_environment->getConfiguration('c_version_addon');
            if ( !empty($c_version_addon) ) {
               $this->assign('environment','commsy_version_addon',$c_version_addon);
            }

        // archive
        $this->assign('environment','archive_mode',$this->_environment->isArchiveMode());
        // archive

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


            if (isset($own_room_item)
                // archive
                and !$this->_environment->isArchiveMode()
                // archive
               ){
                $this->assign('cs_bar', 'show_widgets', $own_room_item->getCSBarShowWidgets());
                $this->assign('cs_bar', 'show_calendar', $own_room_item->getCSBarShowCalendar());
                $this->assign('cs_bar', 'show_stack', $own_room_item->getCSBarShowStack());
                $this->assign('cs_bar', 'show_portfolio', $own_room_item->getCSBarShowPortfolio());
                $this->assign('cs_bar', 'addon_information', $this->getAddonInformation());
                if ( $own_room_item->showCSBarConnection() ) {
                   $this->assign('cs_bar', 'show_connection', $own_room_item->getCSBarShowConnection());
                } else {
                    $this->assign('cs_bar', 'show_connection', false);
                }
            }else{
                $this->assign('cs_bar', 'show_widgets', false);
                $this->assign('cs_bar', 'show_calendar', false);
                $this->assign('cs_bar', 'show_stack', false);
                $this->assign('cs_bar', 'show_portfolio', false);
                $this->assign('cs_bar', 'show_connection', false);
            }
            
            $this->assign('cs_bar', 'show_limesurvey',  !($this->_environment->inPortal() || $this->_environment->inServer()) &&
                                                        $current_context->isLimeSurveyActive() &&
                                                        $portal_item->isLimeSurveyActive() &&
                                                        $portal_item->withLimeSurveyFunctions() );

            // to javascript
            $to_javascript = array();

            $to_javascript['template']['tpl_path'] = $this->_tpl_path;
            $to_javascript['environment']['lang'] = $this->_environment->getSelectedLanguage();
            $to_javascript['environment']['single_entry_point'] = $this->_environment->getConfiguration('c_single_entry_point');
            $to_javascript['environment']['max_upload_size'] = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
            $to_javascript['environment']['portal_link_status'] = $portal_item->getProjectRoomLinkStatus();     // optional | mandatory
            $to_javascript['environment']['isPortal'] = $this->_environment->getCurrentContextItem()->isPortal();

            // escape ' and replace it with \x27
            $to_javascript['environment']['user_name'] = str_replace("'", '\x27', $current_user->getFullName());

            $current_portal_user = $this->_environment->getPortalUserItem();
            // password expires soon alert
            if(!empty($current_portal_user) AND $current_portal_user->getPasswordExpireDate() > getCurrentDateTimeInMySQL()) {
                $start_date = new DateTime(getCurrentDateTimeInMySQL());
                $since_start = $start_date->diff(new DateTime($current_portal_user->getPasswordExpireDate()));
                $days = $since_start->days;
                if($days == 0){
                    $days = 1;
                }

                $days_before_expiring_sendmail = $portal_item->getDaysBeforeExpiringPasswordSendMail();
                if(isset($days_before_expiring_sendmail) AND $days <= $days_before_expiring_sendmail){
                    $to_javascript["translations"]["password_expire_soon_alert"] = $translator->getMessage("COMMON_PASSWORD_EXPIRE_ALERT", $days);
                    $to_javascript['environment']['password_expire_soon'] = true;
                } else if(!isset($days_before_expiring_sendmail) AND $days <= 14){
                    $to_javascript["translations"]["password_expire_soon_alert"] = $translator->getMessage("COMMON_PASSWORD_EXPIRE_ALERT", $days);
                    $to_javascript['environment']['password_expire_soon'] = true;
                }
            } else {
                $to_javascript['environment']['password_expire_soon'] = false;
            }

            global $symfonyContainer;
            
            // locking
            $checkLocking = $symfonyContainer->getParameter('commsy.settings.item_locking');
            $to_javascript["environment"]["item_locking"] = $checkLocking;

            // single categorie selection
            $singleCatSelection = $symfonyContainer->getParameter('commsy.settings.single_cat_selection');
            $to_javascript["environment"]["single_cat_selection"] = $singleCatSelection;
            
            $to_javascript['i18n']['COMMON_NEW_BLOCK'] = $translator->getMessage('COMMON_NEW_BLOCK');
            $to_javascript['i18n']['COMMON_SAVE_BUTTON'] = $translator->getMessage('COMMON_SAVE_BUTTON');
            $to_javascript['security']['token'] = getToken();
            $to_javascript['autosave']['mode'] = 0;
            $to_javascript['autosave']['limit'] = 0;

            $to_javascript["environment"]['portal_id'] = $this->_environment->getCurrentPortalID();

            global $c_media_integration;
            if($c_media_integration) {
                $to_javascript['c_media_integration'] = true;
                // check for rights for mdo
                $current_context_item = $this->_environment->getCurrentContextItem();
                if($current_context_item->isProjectRoom()) {
                    // does this project room has any community room?
                    $community_list = $current_context_item->getCommunityList();
                    if($community_list->isNotEmpty()) {
                        // check for community rooms activated the mdo feature
                        $community = $community_list->getFirst();
                        while($community) {
                            $mdo_active = $community->getMDOActive();
                            if(!empty($mdo_active) && $mdo_active != '-1') {
                                $to_javascript['mdo_active'] = true;
                                break;
                            }
                            $community = $community_list->getNext();
                        }
                    }
                }
            } else {
                $to_javascript['c_media_integration'] = false;
            }


            if ($ownRoomItem)
            {
                $to_javascript['own']['id'] = $ownRoomItem->getItemId();
                $to_javascript['ownRoom']['id'] = $ownRoomItem->getItemId();
                $to_javascript['ownRoom']['withPortfolio'] = $own_room_item->getCSBarShowPortfolio();
            }

            // translations - should be managed elsewhere soon
            $to_javascript["translations"]["common_hide"] = $translator->getMessage("COMMON_HIDE");
            $to_javascript["translations"]["common_show"] = $translator->getMessage("COMMON_SHOW");
            
            $current_user = $this->_environment->getCurrentUserItem();
            
            $auth_source_manager = $this->_environment->getAuthSourceManager();
            $auth_source_item = $auth_source_manager->getItem($current_user->getAuthSource());
            
            if(isset($auth_source_item)){
                $show_tooltip = true;
                // password
                if($auth_source_item->getPasswordLength() > 0){
                    $to_javascript["password"]["length"] = $translator->getMessage('PASSWORD_INFO2_LENGTH', $auth_source_item->getPasswordLength());
                } else {
                    $show_tooltip = false;
                }
                if($auth_source_item->getPasswordSecureBigchar() == 1){
                    $to_javascript["password"]["big"] = $translator->getMessage('PASSWORD_INFO2_BIG');
                } else {
                    $show_tooltip = false;
                }
                if($auth_source_item->getPasswordSecureSmallchar() == 1){
                    $to_javascript["password"]["small"] = $translator->getMessage('PASSWORD_INFO2_SMALL');
                } else {
                    $show_tooltip = false;
                }
                if($auth_source_item->getPasswordSecureNumber() == 1){
                    $to_javascript["password"]["special"] = $translator->getMessage('PASSWORD_INFO2_SPECIAL');
                } else {
                    $show_tooltip = false;
                }
                if($auth_source_item->getPasswordSecureSpecialchar() == 1){
                    $to_javascript["password"]["number"] = $translator->getMessage('PASSWORD_INFO2_NUMBER');
                } else {
                    $show_tooltip = false;
                }
            } else {
                $show_tooltip = false;
            }
            if($show_tooltip){
                $to_javascript["password"]["tooltip"] = 1;
            } else {
                $to_javascript["password"]["tooltip"] = 0;
            }
            
            if ($this->_environment->getCurrentFunction() == 'detail'
                        and $this->_environment->getCurrentModule() == 'group'){
                $params = $this->_environment->getCurrentParameterArray();
                $group_manager = $this->_environment->getGroupManager();
                $group_item = $group_manager->getItem($params['iid']);
                if ($group_item->isGroupRoomActivated() ){
                    $to_javascript['dev']['room_id'] = $group_item->getGroupRoomItemID();
                }
            }
            
        

            // dev
            global $c_xhr_error_reporting;
            $this->assign('environment','with_indexed_search',false);
            $to_javascript['dev']['indexed_search'] = false;
            $to_javascript['dev']['xhr_error_reporting'] = (isset($c_xhr_error_reporting) && !empty($c_xhr_error_reporting)) ? true : false;

            if(isset($portal_user) && $portal_user->isAutoSaveOn()) {
                global $symfonyContainer;

                $c_autosave_mode = $symfonyContainer->getParameter('commsy.autosave.mode');
                $c_autosave_limit = $symfonyContainer->getParameter('commsy.autosave.limit');

                if(isset($c_autosave_mode) && isset($c_autosave_limit)) {
                    $to_javascript['autosave']['mode'] = $c_autosave_mode;
                    $to_javascript['autosave']['limit'] = $c_autosave_limit;
                }
            }
            
            // limesurvey
            if (    !($this->_environment->inPortal() || $this->_environment->inServer()) &&
                    $current_context->isLimeSurveyActive() &&
                    $portal_item->isLimeSurveyActive() &&
                    $portal_item->withLimeSurveyFunctions() )
            {
                $rpcPathParsed = parse_url($portal_item->getLimeSurveyJsonRpcUrl());
                $matches = array();
                preg_match('/(.*)\/index.php/', $rpcPathParsed['path'], $matches);
                $subPath = '';
                if (isset($matches[1])) {
                    $subPath = $matches[1];
                }
                
                $to_javascript["limesurvey"]["newSurveyPath"] = $rpcPathParsed['scheme'] . "://" . $rpcPathParsed['host'] . $subPath .  "/index.php/admin/survey/sa/index";
                $to_javascript["limesurvey"]["adminPath"] = $rpcPathParsed['scheme'] . "://" . $rpcPathParsed['host'] . $subPath . "/index.php/admin/";
                $to_javascript["limesurvey"]["roomName"] = $current_context = $current_context->getTitle();
            }
            
            // mixin javascript variables
            if(is_array($this->_toJSMixin)) {
                $to_javascript = array_merge($to_javascript, $this->_toJSMixin);
            }

            $this->assign('javascript', 'variables_as_json', json_encode($to_javascript));

            $this->assign("javascript", "locale", $this->_environment->getSelectedLanguage());

            // version
            global $c_debug;
            if ( isset($c_debug) && $c_debug === true )
            {
                $this->assign("javascript", "version", uniqid("", true));
            }
            else
            {
                if ( file_exists("version") )
                {
                    $versionFromFile = trim(file_get_contents("version"));

                    /*
                     * It is very important to replace " " whitespaces, otherwhise dojo shows some odd behaviour
                     * resulting in adding y11n body classes(high contrast css)
                     */
                    $this->assign("javascript", "version", str_replace(" ", "_", $versionFromFile));
                }
                else
                {
                    $this->assign("javascript", "version", "unset");
                }
            }
            
            // plugins
            $info_before_body_ends = LF.'   <!-- PLUGINS BEGIN -->'.LF;
            include_once('functions/misc_functions.php');
            $info_before_body_ends .= plugin_hook_output_all('getInfosForBeforeBodyEndAsHTML',array(),LF,false).LF;
            $info_before_body_ends .= '   <!-- PLUGINS END -->'.LF.LF;
            $this->assign('basic', 'html_before_body_ends', $info_before_body_ends);
        }
    }
