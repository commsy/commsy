<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

// pretend, we work from the CommSy basedir to allow
// giving include files without "../" prefix all the time.

chdir('..');

function cleanupSession($session, $environment){
     if ($session->issetValue('history')){
        $history_array = $session->getValue('history');
        $used_modules = array();
        foreach($history_array as $history){
           $used_modules[] = $history['module'];
        }
        $context_item = $environment->getCurrentContextItem();
        $current_room_modules = $context_item->getHomeConf();
        $room_modules = array();
        if (!empty($current_room_modules)) {
           $room_modules =  explode(',',$current_room_modules);
        }
        foreach ($room_modules as $module) {
           $module_name = explode('_',$module);
           $name = $module_name[0];
           if ( !in_array($name,$used_modules) and !in_array('home',$used_modules)) {
              $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$name.'_index_ids');
              $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$name.'s_index_ids');
              $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$name.'_selected_ids');
              $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$name.'s_selected_ids');
           }
        }
        if ( !in_array('account',$used_modules) ){
           $session->unsetValue('cid'.$environment->getCurrentContextID().'_account_index_ids');
           $session->unsetValue('cid'.$environment->getCurrentContextID().'_account_selected_ids');
        }
     }
     if ($environment->getCurrentFunction() != 'edit'){
        $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
        $session->unsetValue('buzzword_post_vars');
        $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
        $session->unsetValue('tag_post_vars');
        $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
        $session->unsetValue('linked_items_post_vars');
     }
}

// include base-config
include_once('etc/cs_constants.php');
@include_once('etc/cs_config.php');
if ( !isset($db) ) {
   header('Location: install');
   header('HTTP/1.0 302 Found');
   exit();
}
include_once('functions/misc_functions.php');

// start of execution time
$time_start = getmicrotime();

// transform POST_VARS and GET_VARS --- move into page object, if exist
include_once('functions/text_functions.php');
$_POST = encode(FROM_FORM,$_POST);
$_GET  = encode(FROM_GET,$_GET);
$_GET  = encode(FROM_FORM,$_GET);

// setup/check required PHP configuration
if ( get_magic_quotes_gpc() ) {
   include_once('functions/error_functions.php');
   trigger_error('"magic_quotes_gpc" must be switched off for CommSy to work correctly. This must be set in php.ini, .htaccess or httpd.conf.', E_USER_ERROR);
}
if ( get_magic_quotes_runtime() ) {
   ini_set('magic_quotes_runtime',0);
   if ( get_magic_quotes_runtime() ) {
      include_once('functions/error_functions.php');
      trigger_error('"magic_quotes_runtime" must be switched off for CommSy to work correctly. See "htaccess-dist".', E_USER_ERROR);
   }
}
$ini_reg_globals = ini_get('register_globals');
if ( !empty($ini_reg_globals) and strtolower($ini_reg_globals) != 'off' ) {
   include_once('functions/error_functions.php');
   trigger_error('"register_globals" must be switched off for CommSy to work correctly. This must be set in php.ini, .htaccess or httpd.conf.', E_USER_ERROR);
}

// setup class factory
include_once('classes/cs_class_factory.php');
$class_factory = new cs_class_factory();

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();

// include classes needed for this script
include_once('classes/cs_session_item.php');
include_once('classes/cs_session_manager.php');
$current_user = $environment->getCurrentUser();

/*********** INITIALIZE ENVIRONMENT ***********/

// initialize environment
// - context id
// - module
// - function
// and context object
$cid_not_set = false;
if ( isset($_GET['cid']) ) {
   $current_context = $_GET['cid'];
} elseif ( isset($_POST['cid']) ) {
   $current_context = $_POST['cid'];
} else {
   $current_context = 99;
   $cid_not_set = true;
}

if ( !isURLValid() ) {
   if ( !empty($_GET['mod'])
        and !empty($_GET['fct'])
        and isPlugin($_GET['mod'])
      ) {
      $current_module = CS_PLUGIN_TYPE;
      $current_function = 'index';
      $plugin_module = $_GET['mod'];
      $plugin_function = $_GET['fct'];
   } elseif ( !empty($_POST['mod'])
              and !empty($_POST['fct'])
              and isPlugin($_POST['mod'])
      ) {
      $current_module = CS_PLUGIN_TYPE;
      $current_function = 'index';
      $plugin_module = $_POST['mod'];
      $plugin_function = $_POST['fct'];
   } else {
      $current_module = 'home';
      $current_function = 'index';
   }
} else {
   if ( !isset($_GET['mod']) or !isset($_GET['fct']) ) {
      $current_module = 'home';
      $current_function = 'index';
   } else {
      if ( isset($_GET['mod']) ) {
         $current_module = $_GET['mod'];
      }
      if ( isset($_GET['fct']) ) {
         $current_function = $_GET['fct'];
      }
   }
}

$environment->setCurrentContextID($current_context);
$environment->setCurrentModule($current_module);
$environment->setCurrentFunction($current_function);
unset($current_context);
#unset($current_module);
#unset($current_function);

// set output mode: default is html
if ( $environment->getCurrentFunction() == 'index'
     and $environment->getCurrentModule() == type2Module(CS_MATERIAL_TYPE)
   ) {
   if ( !empty($_GET['output']) ) {
      $environment->setOutputMode($_GET['output']);
   } elseif ( !empty($_POST['output']) ) {
      $environment->setOutputMode($_POST['output']);
   }
}

// switch: CommSy6 / CommSy7
if ( $environment->inProjectRoom()
     or $environment->inCommunityRoom()
     or $environment->inGroupRoom()
     or $environment->inPrivateRoom()
   ) {
   $current_context_item = $environment->getCurrentContextItem();
   if ( $current_context_item->isDesign6() ) {
      $class_factory->setDesignTo6();
   } else {
      $class_factory->setDesignTo7();
   }
}else{
   $class_factory->setDesignTo6();
}

$server_item = $environment->getServerItem();
if ( $server_item->showOutOfService() ) {
   $current_module_save = $current_module;
   $current_function_save = $current_function;
   $current_module = 'home';
   $current_function = 'outofservice';
   $environment->setCurrentModule($current_module);
   $environment->setCurrentFunction($current_function);
   $environment->setCurrentContextID($server_item->getItemID());
   $outofservice = true;
} else {
   $outofservice = false;
}
unset($server_item);

$context_item_current = $environment->getCurrentContextItem();

/*********** SERVER INITIALIZATION AND JUMP TO SINGLE PORTAL ***********/

// send user to ...
// portal home, if only one exists and cid in URL was empty
// or to server initialization, if server item does not exist (not implemented yet)
if ( !isset($context_item_current)
     and $current_context == 99
     and $cid_not_set
     and !$outofservice
   ) {
   // server initialization
   include_once('functions/error_functions.php');
   trigger_error('Server initialization is not implemented yet',E_USER_ERROR);
} elseif ( $context_item_current->isServer()
           and $cid_not_set
           and !$outofservice
         ) {
   $portal_list = $context_item_current->getPortalList();
   if ($portal_list->getCount() == 1) {
      $current_portal = $portal_list->getFirst();
      $environment->setCurrentContextID($current_portal->getItemID());
   }
   unset($portal_list);
}

/*********** SESSION AND AUTHENTICATION ***********/

// get Session ID (SID)
if (!empty($_GET['SID'])) {
   $SID = $_GET['SID'];                     // session id via GET-VARS (url)
} elseif (!empty($_POST['SID'])) {
   $SID = $_POST['SID'];                    // session id via POST-VARS (form posts)
} elseif (!empty($_COOKIE['SID'])) {
   $SID = $_COOKIE['SID'];
   $session_manager = $environment->getSessionManager();
   $session = $session_manager->get($SID);
   if ( !isset($session)
        and $environment->getCurrentModule() == 'context'
        and $environment->getCurrentFunction() == 'login'
        and !$outofservice
      ) {
      include_once('pages/context_login.php');
      exit();
   }
} elseif ( $environment->getCurrentModule() == 'context'
           and $environment->getCurrentFunction() == 'login'
           and !$outofservice
         ) {
   include_once('pages/context_login.php');
   exit();
} elseif ( strtolower($environment->getCurrentFunction()) == 'getfile'
           and strtolower($environment->getCurrentModule()) == 'picture'
         ) {
   include_once('pages/picture_getfile.php');
   exit();
} else {
   // no session created
   // so create session and redirect to requested page
   $session = new cs_session_item();
   $session->createSessionID('guest');
   $current_portal_id = $environment->getCurrentPortalID();
   if ( !empty($current_portal_id) ) {
      $session->setValue('commsy_id',$current_portal_id);
   } else {
      $server_id = $environment->getServerID();
      if ( !empty($server_id) ) {
         $session->setValue('commsy_id',$server_id);
      }
   }

   // external reload to check javascript
   $session_manager = $environment->getSessionManager();
   $session_manager->save($session);
   if ( !$outofservice ) {
      include_once('pages/context_reload.php');
      exit();
   } else {
      $SID = $session->getSessionID();
   }
}

if ( !empty($SID) ) {
   // there is a session_id,
   // and there must be a session,
   // so we can load the session information
   $session_manager = $environment->getSessionManager();
   $session = $session_manager->get($SID);

   /** password forget (BEGIN) **/
   if ( isset($session) and $session->issetValue('password_forget_ip') ) {
      $session_time = $session->issetValue('password_forget_time');
      $session_ip = $session->issetValue('password_forget_ip');
      $current_ip = '127.0.0.1';
      if ( isset($_SERVER["SERVER_ADDR"]) and !empty($_SERVER["SERVER_ADDR"])) {
         $current_ip = $_SERVER["SERVER_ADDR"];
      } else {
         $current_ip = $_SERVER["HTTP_HOST"];
      }
      include_once('functions/date_functions.php');

      if ( $session_time < getCurrentDateTimeMinusMinutesInMySQL(15)
           or $current_ip != $session_ip) {
         $session_manager->delete($SID,true);
         if (isset($session)){
            $session->reset(); // so session will not saved at redirect
         }
         $url = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
         if (mb_stristr($url,'&SID=')) {
            $url = mb_substr($url,0,mb_strpos($url,'&SID='));
         }
         redirect_with_url($url);
      } else {
         $_GET['cs_modus'] = 'password_change';
         $environment->setCurrentModule('home');
         $environment->setCurrentFunction('index');
         if ( !$environment->inPortal() ) {
            $environment->setCurrentContextID($environment->getCurrentPortalID());
         }
         $environment->getCurrentParameterArray();
         $environment->setCurrentParameter('cs_modus','password_change');
      }
   }
   /** password forget (END) **/

   if ( isset($session)
        and mb_strtoupper($session->getValue('user_id'), 'UTF-8') == 'GUEST'
        and !$outofservice
      ) {
      $cas_ticket = '';
      if ( !empty($_GET['ticket']) ) {
         $cas_ticket = $_GET['ticket'];
      } elseif ( !empty($_POST['ticket']) ) {
         $cas_ticket = $_POST['ticket'];
      } elseif ( !empty($_COOKIE['ticket']) ) {
         $cas_ticket = $_COOKIE['ticket'];
      }

      if ( !empty($cas_ticket) ) {
         $portal = $environment->getCurrentPortalItem();
         $cas_list = $portal->getAuthSourceListCASEnabled();
         if ( $cas_list->isNotEmpty() ) {
            $cas_auth_source = $cas_list->getFirst();
            while ($cas_auth_source) {
               $authentication = $environment->getAuthenticationObject();
               $cas_manager = $authentication->getAuthManagerByAuthSourceItem($cas_auth_source);
               $user_id = $cas_manager->validateTicket($cas_ticket);
               if ( isset($user_id) and !empty($user_id) ) {
                  $auth_source = $cas_auth_source->getItemID();
                  $portal_item = $environment->getCurrentPortalItem();
                  $user_item = $authentication->getPortalUserItem($user_id,$auth_source);
                  $user_item_id = $user_item->getItemID();
                  if ( empty($user_item_id) ) {
                     $params = array();
                     $params = $environment->getCurrentParameterArray();
                     $params['user_id'] = $uid;
                     $params['auth_source'] = $auth_source;
                     $params['cs_modus'] = 'portalmember2';
                     $session_item = $environment->getSessionItem();
                     if ( isset($session_item) ) {
                        $history = $session_item->getValue('history');
                        $module = $history[0]['module'];
                        $funct = $history[0]['function'];
                        unset($session_item);
                     } else {
                        $module = $this->_environment->getCurrentModule();
                        $funct = $this->_environment->getCurrentFunction();
                     }
                     redirect( $environment->getCurrentContextID(),
                               $module,
                               $funct,
                               $params
                             );
                     unset($params);
                     exit();
                  } else {
                     include_once('include/inc_make_session_for_user.php');
                     $environment->setSessionItem($session);
                  }
                  unset($user_item);
                  unset($portal_item);
                  break;
               }
               $cas_auth_source = $cas_list->getNext();
            }
         }
         unset($portal);
      }
   }

   if (isset($session) and $session->issetValue('user_id')) {       // session is in database, so session is valid and user has already logged on
      if (!$session->issetValue('cookie')) {    // second time a user get a commsy page
         if (isset($_COOKIE['SID'])) { // are cookies allowed?
            $session->setValue('cookie','1');   // yes
         } else {
            $session->setValue('cookie','0');   // no
         }
      }

      // commsy id in session and on current page is different
      // -> user manipulated the url
      // -> redirect to home index of the portal, if user != guest and user != root
      $session_commsy_id = $session->getValue('commsy_id');
      if ( $environment->inProjectRoom()
           or $environment->inCommunityRoom()
           or $environment->inPrivateRoom()
           or $environment->inGroupRoom()
         ) {
         $portal_id = $environment->getCurrentPortalID();
      } else {
         $portal_id = $environment->getCurrentContextID();
      }
      if ( $session_commsy_id != $portal_id and
           $session->getValue('user_id') != 'guest' and
           $session->getValue('user_id') != 'root' and
           $environment->getCurrentFunction() != 'getfile'
           and $environment->getCurrentFunction() != 'getingray'
           and !$outofservice
         ) {
         redirect($session_commsy_id,'home','index');
      }

      $authentication = $environment->getAuthenticationObject();
      $authentication->setModule($current_module);
      $authentication->setFunction($current_function);

      // check, if user is allowed here in this context (no password uid evaluation)
      // and set current user
      if ( !$authentication->check($session->getValue('user_id'),$session->getValue('auth_source'))
           and $environment->getCurrentFunction() != 'logout'
           and $environment->getCurrentFunction() != 'change'
         ) {
         ###############################################
         # show error box in room
         ###############################################
         #$params = array();
         #$params['environment'] = $environment;
         #$params['with_modifying_actions'] = true;
         #$errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         #unset($params);
         #$error_array = $authentication->getErrorArray();
         #if (!empty($error_array)) {
         #   $error_string = implode('<br />',$error_array);
         #   $errorbox->setText($error_string);
         #} else {
         #   $errorbox->setText($translator->getMessage('COMMON_ERROR'));
         #}

         ###############################################
         # goto portal "vor die Tuer"
         ###############################################
         $parameter_array = $environment->getCurrentParameterArray();
         $parameter_array['cid'] = $environment->getCurrentContextID();
         $parameter_array['mod'] = $environment->getCurrentModule();
         $parameter_array['fct'] = $environment->getCurrentFunction();
         $session->setValue('login_redirect',$parameter_array);

         $environment->setCurrentParameter('room_id',$environment->getCurrentContextID());
         $environment->setCurrentContextID($environment->getCurrentPortalID());
         $environment->setCurrentModule('home');
         $environment->setCurrentFunction('index');

         $current_module = $environment->getCurrentModule();
         $current_function = $environment->getCurrentFunction();
      }
      $current_user = $authentication->getUserItem();
      $environment->setCurrentUserItem($current_user);
   } elseif (!$outofservice) {
      // there is no user id in the session information, or no session
      // so delete session and just turn to the beginning of the process
      // a new unknown user comes to a commsy page ...
      $session_manager->delete($SID,true);
      if (isset($session)){
         $session->reset(); // so session will not saved at redirect
      }
      $url = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
      if (mb_stristr($url,'&SID=')) {
         $url = mb_substr($url,0,mb_strpos($url,'&SID='));
      }
      redirect_with_url($url);
   }
}

$translator = $environment->getTranslationObject();

/************ security: prevent session riding **************/
if ( !empty($_SERVER['SERVER_ADDR'])
     and isset($session)
     and $session->issetValue('IP')
     and $session->getValue('IP') != $_SERVER['SERVER_ADDR']
   ) {
   include_once('functions/error_functions.php');
   trigger_error('Cross Site Request Forgery detected. Request aborted.',E_USER_ERROR);
}
if ( !empty($_POST)
     and !empty($SID)
     and !( $environment->getCurrentModule() == 'file'
            and $environment->getCurrentFunction() == 'upload'
          )
     and !( $environment->getCurrentModule() == 'context'
            and $environment->getCurrentFunction() == 'login'
          )
     and !( $environment->getCurrentModule() == 'room'
            and $environment->getCurrentFunction() == 'change'
          )
   ) {
   $csrf_error = false;
   if ( empty($_POST['security_token']) and empty($_GET['security_token']) ) {
      $csrf_error = true;
   } else {
      include_once('functions/security_functions.php');
      if ( !empty($_POST['security_token']) and getToken() != $_POST['security_token'] ) {
         $csrf_error = true;
      } elseif ( !empty($_GET['security_token']) and getToken() != $_GET['security_token'] ) {
         $csrf_error = true;
      }
   }
   if ( $csrf_error ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ERROR_CROSS_SITE_REQUEST_FORGERY'));
   }
   unset($csrf_error);
}

/************ language management **************/
if ( isset($_POST['message_language_select']) ) {
   if ( empty($_POST['message_language_select'])
        or $_POST['message_language_select'] == 'reset'
      ) {
      $session->unsetValue('message_language_select');
   } else {
      $session->setValue('message_language_select',$_POST['message_language_select']);
   }
}

/*********** javascript check *************/
if ( !$outofservice
     and $environment->isOutputModeNot('XML')
     and !$session->issetValue('javascript')
     and !isset($_GET['jscheck'])
     and !( $environment->getCurrentModule() == 'file'
            and $environment->getCurrentFunction() == 'upload'
          )
     and !( $environment->getCurrentModule() == 'material'
            and $environment->getCurrentFunction() == 'getfile'
          )
     and !( $environment->getCurrentModule() == 'room'
            and $environment->getCurrentFunction() == 'change'
          )
   ) {
   include_once('pages/context_reload.php');
   exit();
}
if ( isset($_GET['jscheck'])
     and $environment->isOutputModeNot('XML')
     and ( empty($_POST)
           or ( count($_POST) == 1
                and !empty($_POST['HTTP_ACCEPT_LANGUAGE']) // bugfix: php configuration
              )
         )
   ) {
   $session = $environment->getSessionItem();
   if ( isset($session) and !$session->issetValue('javascript')) {
      if (isset($_GET['isJS'])) {
         $session->setValue('javascript',1);
      } else {
         $session->setValue('javascript',-1);
      }
   }
}

$current_user_item = $environment->getCurrentUserItem();
if ( $outofservice
     and $current_user_item->isRoot()
   ) {
   $current_module = 'configuration';
   if ( $current_function_save != 'outofservice' ) {
      $current_function = 'outofservice';
   } else {
      $current_function = $current_function_save;
   }
   $environment->setCurrentModule($current_module);
   $environment->setCurrentFunction($current_function);
}

/*********** forward ********/
if ( $environment->getCurrentModule() == 'context'
     and  $environment->getCurrentFunction() == 'forward'
     and !$outofservice
   ) {
   include_once('pages/context_forward.php');
   exit();
}

/*********** PAGE ***********/
// with or without modifiying options
$with_modifying_actions = $context_item_current->isOpen();

if ( $environment->isOutputMode('XML') ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $page = $class_factory->getClass(PAGE_XML_VIEW,$params);
   unset($params);
} else {
   $parameters = $environment->getCurrentParameterArray();
   if (isset($parameters['mode']) and $parameters['mode']=='print') {
      if (isset($parameters['view_mode']) and $parameters['view_mode']=='pda') {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $with_modifying_actions;
         $page = $class_factory->getClass(PAGE_PDA_VIEW,$params);
         unset($params);
      } else {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $with_modifying_actions;
         $page = $class_factory->getClass(PAGE_PRINT_VIEW,$params);
         unset($params);
      }
   } else {
      $temp_module = $environment->getCurrentModule();
      if ( isset($parameters['view_mode'])
           and $parameters['view_mode']=='pda'
         ) {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $with_modifying_actions;
         $page = $class_factory->getClass(PAGE_PDA_VIEW,$params);
         unset($params);
      }
      if ( $temp_module == 'help' ) {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $with_modifying_actions;
         $page = $class_factory->getClass(PAGE_HELP_VIEW,$params);
         unset($params);
      } else {
         // create page object
         if ( $environment->inProjectRoom()
              or $environment->inCommunityRoom()
              or $environment->inPrivateRoom()
              or $environment->inGroupRoom()
            ) {
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $with_modifying_actions;
            $page = $class_factory->getClass(PAGE_ROOM_VIEW,$params);
            unset($params);
         } elseif ( $environment->inPortal() ) {
            $context_item = $environment->getCurrentContextItem();
            $filename = 'external_pages/'.$context_item->getItemID().'/cs_external_page_portal_view.php';
            if ( file_exists($filename) ) {
               include_once($filename);
               $params = array();
               $params['environment'] = $environment;
               $params['with_modifying_actions'] = $with_modifying_actions;
               $page = new cs_external_page_portal_view($params);
               unset($params);
            } else {
               $params = array();
               $params['environment'] = $environment;
               $params['with_modifying_actions'] = $with_modifying_actions;
               $page = $class_factory->getClass(PAGE_GUIDE_VIEW,$params);
               unset($params);
            }
         } else {
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $with_modifying_actions;
            $page = $class_factory->getClass(PAGE_GUIDE_VIEW,$params);
            unset($params);
         }
      }
   }
}

if ( isset($session) ) {
   $left_menue_status = $session->getValue('left_menue_status');
   if ( isset($_GET['left_menue']) and !empty($_GET['left_menue']) ){
      $session->setValue('left_menue_status', $_GET['left_menue']);
   }
}

if ( $environment->isOutputModeNot('XML') ) {
   $page->setCurrentUser($environment->getCurrentUserItem());

   // set title
   $title = $context_item_current->getTitle();
   if ($context_item_current->isProjectRoom() and $context_item_current->isTemplate()) {
      $title .= ' ('.$translator->getMessage('PROJECTROOM_TEMPLATE').')';
   } elseif ($context_item_current->isClosed()) {
      $title .= ' ('.$translator->getMessage('PROJECTROOM_CLOSED').')';
   }

   $user = $environment->getCurrentUserItem();
   if ( $context_item_current->isPrivateRoom() and $user->isGuest() ) {
      $page->setRoomName($translator->getMessage('COMMON_FOREIGN_ROOM'));
      $page->setPageName($translator->getMessage('COMMON_FOREIGN_ROOM'));
   } elseif ( $context_item_current->isPrivateRoom() ) {
      $page->setRoomName($translator->getMessage('COMMON_PRIVATEROOM'));
      $tempModule = mb_strtoupper($environment->getCurrentModule(), 'UTF-8');
      $tempMessage = "";
      include_once('include/inc_commsy_php_case_pagetitle.php');
      $page->setPageName($tempMessage);
   } else {
      $page->setRoomName($title);
      $tempModule = mb_strtoupper($environment->getCurrentModule(), 'UTF-8');
      $tempMessage = "";
      include_once('include/inc_commsy_php_case_pagetitle.php');
      $page->setPageName($tempMessage);
   }
}

// display login errors
if ( isset($session) and $session->issetValue('error_array') ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $errorbox_left = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox_left->setText(implode('<br/>',$session->getValue('error_array')));
   $session->unsetValue('error_array');
   $page->setMyAreaErrorBox($errorbox_left);
}

// check if portal exists
if ( !$environment->inServer() and !$environment->inPortal() ) {
   $current_portal = $environment->getCurrentPortalItem();
   if ( $current_portal->isDeleted() ) {
      $current_context = $environment->getCurrentContextItem();
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('PORTAL_ERROR_DELETED_ROOM',$current_portal->getTitle(),$current_context->getTitle()));
      $page->setWithoutLeftMenue();
   }
}

// AGB
$current_user = $environment->getCurrentUserItem();

// portal AGB
$current_context = $environment->getCurrentContextItem();
if ( !$current_context->isPortal()
     and !$current_context->isServer()
   ) {
   $portal_user = $current_user->getRelatedCommSyUserItem();
   if ( isset($portal_user) and $portal_user->isUser() and !$portal_user->isRoot() ) {
      $current_portal = $environment->getCurrentPortalItem();
      $user_agb_date = $portal_user->getAGBAcceptanceDate();
      $portal_agb_date = $current_portal->getAGBChangeDate();
      if ( $user_agb_date < $portal_agb_date
           and $current_portal->getAGBStatus() == 1
         ) {
         redirect($current_portal->getItemID(),'agb','detail');
      }
   }
}

// room AGB
$show_agb_again = false;
if ( $current_user->isUser() and !$current_user->isRoot() ) {
   $user_agb_date = $current_user->getAGBAcceptanceDate();
   $context_agb_date = $current_context->getAGBChangeDate();
   if ( $user_agb_date < $context_agb_date
        and $current_context->getAGBStatus() == 1
      ) {
      $show_agb_again = true;
   }
}

// agb, errorbox or include page
if ( $show_agb_again ) {
   if ( ($current_module == 'picture')
        and ( $current_function == 'getfile'
              or $current_function == 'getingray'
            )
      ) {
      include('pages/'.$current_module.'_'.$current_function.'.php');
   } else {
      include_once('pages/agb_detail.php');
   }
} elseif ( !isset($errorbox) ) {
   // JCommSy delegation
   if ( isset($jcommsy)
        and array_key_exists($current_module,$jcommsy)
        and $jcommsy[$current_module]
        and ( !isset($_GET['mode']) or ( isset($_GET['mode']) and $_GET['mode']!='detailattach' ) )
        and ( !isset($_POST['mode']) or ( isset($_POST['mode']) and $_POST['mode']!='detailattach' ) )

        // attach announcement to announcement
        and ( !isset($_GET['mode']) or ( isset($_GET['mode']) and $_GET['mode']!='formattach' ) )
        and ( !isset($_POST['mode']) or ( isset($_POST['mode']) and $_POST['mode']!='formattach' ) )

        and ( !isset($_GET['mode']) or ( isset($_GET['mode']) and $_GET['mode']!='print' ) )
        and ( !isset($_POST['mode']) or ( isset($_POST['mode']) and $_POST['mode']!='print' ) )
        and ( !isset($_GET['fct']) or ( isset($_GET['fct']) and $_GET['fct']!='clipboard_index' ) )
        and ( !isset($_POST['fct']) or ( isset($_POST['fct']) and $_POST['fct']!='clipboard_index' ) )
        and isset($java_enabled_for)
        and (in_array($environment->getCurrentContextId(),$java_enabled_for) or in_array($environment->getCurrentPortalId(),$java_enabled_for)))
   {
      $param = parameterString($_GET,$_POST);
      header("Location: http://".$jcommsy['Servlet-URL'].'?mod='.$current_module.'&fct='.$current_function.$param);
   } elseif ( !file_exists('pages/'.$current_module.'_'.$current_function.'.php') ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $with_modifying_actions;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText('The page '.$current_module.'_'.$current_function.' cannot be found!');
      $page->add($errorbox);

      $current_user = $environment->getCurrentUserItem();
      if (!isset($current_user) or $current_user->getUserID() == '') {
         $page->setWithoutPersonalArea();
      }
   } else {
      if ( $current_function == 'edit'
           and $current_context->isDesign7()
           and (
                 $current_module == CS_MATERIAL_TYPE
                 or $current_module == CS_ANNOUNCEMENT_TYPE
                 or $current_module == CS_DATE_TYPE
                 or $current_module == CS_DISCUSSION_TYPE
                 or $current_module == CS_GROUP_TYPE
                 or $current_module == CS_TODO_TYPE
                 or $current_module == CS_TOPIC_TYPE
                 or $current_module == CS_INSTITUTION_TYPE
               )
         ) {
         include('pages/commsy7/'.$current_module.'_'.$current_function.'.php');
      } elseif ( $current_function == 'index'
                 and $current_context->isDesign7()
                 and ( $current_module == CS_DATE_TYPE
                       or $current_module == CS_TODO_TYPE
                     )
               ) {
         include('pages/commsy7/'.$current_module.'_'.$current_function.'.php');
      } else {
         include('pages/'.$current_module.'_'.$current_function.'.php');
      }
   }
} else {
   $page->add($errorbox);
   if ( $environment->inPortal() ) {
      include('pages/'.$current_module.'_'.$current_function.'.php');
   }
}

if ( $environment->isOutputModeNot('XML') ) {

   // set navigation links
   $current_room_modules = $context_item_current->getHomeConf();
   if (!empty($current_room_modules)) {
      $room_modules =  explode(',',$current_room_modules);
   }

   // das folgende nur, wenn der Raum auch offen ist
   // ansonsten hinweis
   // TBD
   if ( $environment->inProjectRoom()
        or $environment->inCommunityRoom()
        or $environment->inPrivateRoom()
        or $environment->inGroupRoom()
      ) {
      $page->addAction($translator->getMessage('COMMON_HOME_INDEX'),'','home','index');
      foreach ($room_modules as $item) {
         $link_name = explode('_',$item);
         if ( $link_name[1] != 'none' ) {
            $tempMessage = "";
            switch ( mb_strtoupper($link_name[0], 'UTF-8') )
            {
               case 'ANNOUNCEMENT':
                  $tempMessage = $translator->getMessage('COMMON_ANNOUNCEMENT_INDEX');
                  break;
               case 'DATE':
                  $tempMessage = $translator->getMessage('COMMON_DATE_INDEX');
                  break;
               case 'DISCUSSION':
                  $tempMessage = $translator->getMessage('COMMON_DISCUSSION_INDEX');
                  break;
               case 'GROUP':
                  $tempMessage = $translator->getMessage('COMMON_GROUP_INDEX');
                  break;
               case 'INSTITUTION':
                  $tempMessage = $translator->getMessage('COMMON_INSTITUTION_INDEX');
                  break;
               case 'MATERIAL':
                  $tempMessage = $translator->getMessage('COMMON_MATERIAL_INDEX');
                  break;
               case 'MYROOM':
                  $tempMessage = $translator->getMessage('COMMON_MYROOM_INDEX');
                  break;
               case 'PROJECT':
                  $tempMessage = $translator->getMessage('COMMON_PROJECT_INDEX');
                  break;
               case 'TODO':
                  $tempMessage = $translator->getMessage('COMMON_TODO_INDEX');
                  break;
               case 'TOPIC':
                  $tempMessage = $translator->getMessage('COMMON_TOPIC_INDEX');
                  break;
               case 'USER':
                  $tempMessage = $translator->getMessage('COMMON_USER_INDEX');
                  break;
               default:
                  $text = '';
                  if ( $environment->isPlugin($link_name[0]) ) {
                     $plugin_class = $environment->getPluginClass($link_name[0]);
                     if ( !empty($plugin_class)
                          and method_exists($plugin_class,'getDisplayName')
                        ) {
                        $text = $plugin_class->getDisplayName();
                     }
                  }
                  if ( !empty($text) ) {
                     $tempMessage = $text;
                  } else {
                     $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' commsy.php('.__LINE__.') ');
                  }
                  break;
            }
            $page->addAction($tempMessage,'',$link_name[0],'index');
         }
      }
   }

   // authentication (bookmarks)
   $current_user = $environment->getCurrentUserItem();
   if (!$current_user->isUser() and !$context_item_current->isOpenForGuests()) {
      $page->setWithoutNavigationLinks();
   }

   if ( isset($_GET['show_profile']) and ($_GET['show_profile'] == 'yes') ) {
      include_once('pages/profile_edit.php');
   }

   if ( isset($_GET['show_copies']) and ($_GET['show_copies'] == 'yes') ) {
      include_once('pages/copies_index.php');
   }

   if ( $current_function !='edit'
        and isset($_GET['attach_view'])
        and ($_GET['attach_view'] == 'yes')
        and isset($_GET['attach_type'])
        and !empty($_GET['attach_type'])
      ) {
      switch ( $_GET['attach_type'] ) {
         case 'buzzword':
            include_once('pages/buzzword_attach.php');
            break;
         case 'tag':
            include_once('pages/tag_attach.php');
            break;
         case 'item':
            include_once('pages/item_attach.php');
            break;
     }
   }
}

// display page
header("Content-Type: text/html; charset=utf-8");
if ( $environment->isOutputMode('XML') ) {
   echo($page->getContent());
} else {
   include_once('functions/security_functions.php');
   if ( isset($_GET['download']) and ($_GET['download'] == 'zip') ) {
      include_once('pages/rubric_download.php');
   }
   if ( empty($_GET['cs_modus']) ) {
      $html = $page->asHTMLFirstPart();
      if ( !empty($html) ) {
         echo(addTokenToPost($html));
//         if(!$fast_settings){
//            flush();
//         }
      }
   }
   $html = $page->asHTMLSecondPart();
   if ( !empty($html) ) {
      echo(addTokenToPost($html));
//      if(!$fast_settings){
//         flush();
//      }
   }
   echo(addTokenToPost($page->asHTML()));
}
//if(!$fast_settings){
//   flush();
//}
unset($page);

/*********** SAVE DATETIME OF LAST ACTIVITY ***********/
if ($current_user->isUser() and !$current_user->isRoot()) {
   $current_user->updateLastLogin();
   if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
      if (!isset($portal_user) or empty($portal_user) ){
         $portal_user = $current_user->getRelatedCommSyUserItem();
      }
      if (isset($portal_user)) {
         $portal_user->updateLastLogin();
      }
      unset($portal_user);
   }
}

/*********** SAVE SESSION ***********/

// save session with history information
if ( $environment->getCurrentFunction() != 'getfile'
     and $environment->getCurrentFunction() != 'getingray'
     and $environment->getCurrentModule() != 'help'
     and !($environment->getCurrentModule() == 'agb' and $environment->getCurrentFunction() == 'index')
     and !empty($session)
   ) {
   $history = '';
   $history = $session->getValue('history');
   $current_page['context'] = $environment->getCurrentContextID();
   $current_page['module'] = $current_module;
   $current_page['function'] = $current_function;
   $current_page['parameter'] = $environment->getCurrentParameterArray();

   if ( !isset($_GET['mode']) or ($_GET['mode'] != 'print') ) {
      if (empty($history)) {
         $history[0] = $current_page;
      } else {
         $new_history[0] = $current_page;
         if ($new_history[0] != $history[0]) {
            $history = array_merge($new_history,$history);
         }
      }
      while (count($history) > 5) {
         array_pop($history);
      }
   }
   if(isset($history[0]['parameter']['download'])) {
      unset($history[0]['parameter']['download']);
   }
   unset($current_page);
   $session->setValue('history',$history);
   cleanupSession($session, $environment);
   unset($history);
   // kann sich die session nicht selbst speichern ???
   $session_manager = $environment->getSessionManager();
   $session_manager->update($session);
   unset($session_manager);
} elseif ( $environment->getCurrentModule() == 'agb' and $environment->getCurrentFunction() == 'index' ) {
   $session_manager = $environment->getSessionManager();
   $session_manager->save($session);
   unset($session_manager);
}

/*********** LOGGING ***********/

// Log information to database. If this part is changed, change it in page material_getfile.php, too!
$array = array();
if ( isset($_GET['iid']) ) {
   $array['iid'] = $_GET['iid'];
} elseif ( isset($_POST['iid']) ) {
   $array['iid'] = $_POST['iid'];
}
if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
   $array['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} else {
   $array['user_agent'] = 'No Info';
}

if ( isset($_POST) ) {
   $post_content = array2XML($_POST);
} else {
   $post_content = '';
}
$array['remote_addr']      = $_SERVER['REMOTE_ADDR'];
$array['script_name']      = $_SERVER['SCRIPT_NAME'];
$array['query_string']     = $_SERVER['QUERY_STRING'];
$array['request_method']   = $_SERVER['REQUEST_METHOD'];
$array['post_content']     = $post_content;
$array['user_item_id']     = $current_user->getItemID();
$array['user_user_id']     = $current_user->getUserID();
$array['context_id']       = $environment->getCurrentContextID();
$array['module']           = $current_module;
$array['function']         = $current_function;
$array['parameter_string'] = $environment->getCurrentParameterString();

$log_manager = $environment->getLogManager();
$log_manager->saveArray($array);
unset($log_manager);


/*********** ROOM ACTIVITY ***********/
$activity_points = 1;
$context_item_current = $environment->getCurrentContextItem();
if ( isset($context_item_current) ) {
   $context_item_current->saveActivityPoints($activity_points);
   if ( $context_item_current->isProjectRoom() or $context_item_current->isCommunityRoom() or $environment->inPrivateRoom() ) {
      $current_portal_item = $environment->getCurrentPortalItem();
      if ( isset($current_portal_item) ) {
         $current_portal_item->saveActivityPoints($activity_points);
         unset($current_portal_item);
      }
   }
}
unset($context_item_current);

/*********** DEBUG INFORMATION ***********/
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);

if ( isset($c_show_debug_infos) and $c_show_debug_infos ) {

   $db_connector = $environment->getDBConnector();
   $sql_query_array = $db_connector->getQueryArray();
   $all = count($sql_query_array);
   $unique = count(array_unique($sql_query_array));
   $too_much = $all - $unique;

   // end of execution time
   echo('<hr/>'.LF);
   echo('<h3>Debug Infos</h3>');
   echo('Total execution time: '.$time.' seconds'.BRLF);
   echo('Peak of memory allocated: '.memory_get_peak_usage().BRLF);
   echo('Current of memory allocated: '.memory_get_usage().BRLF);

   if ($all > 70){
      echo('<div style="color:red; font-weight:bold">Zu viele SQL-Statements ('.$all.'). Grenzwert: 70 Statements</div>');
      echo(BRLF);
   }
   if ($too_much > 0){
      echo('<div style="color:red; font-weight:bold">Zu viele doppelte SQL-Statements ('.$too_much.').</div>');
      echo(BRLF);

   }
   if (count(get_included_files()) > 75){
      echo('<div style="color:red; font-weight:bold">Zu viele Included Files ('.count(get_included_files()).'). Grenzwert 75 Files</div>');
      echo(BRLF);
   }

   echo(BRLF);
   echo('Module: '.$environment->getCurrentModule().BRLF);
   echo('Function: '.$environment->getCurrentFunction().BRLF);
   echo('SessionID: '.$session->getSessionID().BRLF);

   echo(BRLF);
   echo('<hr/>'.LF);
   echo('<span style="font-weight:bold;">Params</span>'.BRLF);
   if ( !empty($_GET) ) {
      echo('GET'.BRLF);
      pr($_GET);
   }
   if ( !empty($_POST) ) {
      echo('POST'.BRLF);
      pr($_POST);
   }
   if ( !empty($_COOKIE) ) {
      echo('COOKIE'.BRLF);
      pr($_COOKIE);
   }

   echo(BRLF);
   echo('<hr/>'.LF);
   echo('<span style="font-weight:bold;">Included Files</span>'.LF);
   pr(get_included_files());

   echo(BRLF);
   echo('<hr/>'.LF);
   echo('<span style="font-weight:bold;">Used SQL-Statements</span>'.BRLF);
   echo('Count SQL Queries: '.$all.BRLF);
   echo('Count unique SQL Queries: '.$unique.BRLF);
   echo('Count SQL Queries too much: '.$too_much.BR.BRLF);
   $temp_array = array();
   $too_much_array = array();
   foreach ($sql_query_array as $query) {
      if ( !in_array($query,$temp_array) ) {
         $temp_array[] = $query;
      } else {
         $too_much_array[] = $query;
      }
   }
   if ( !empty($too_much_array) ) {
      echo('This queries are too much:'.LF);
      pr($too_much_array);
   }
   echo('All queries:'.LF);
   pr($sql_query_array);
   pr($session);
}
if ( $environment->isOutputModeNot('XML') ) {
   echo('<!-- Total execution time: '.$time.' seconds -->');
}
?>