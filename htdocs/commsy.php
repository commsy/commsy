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
mb_internal_encoding('UTF-8');
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

// require composer autoloader
require 'vendor/autoload.php';

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

// setup/check required PHP configuration
if ( get_magic_quotes_gpc() ) {
   include_once('functions/error_functions.php');
   trigger_error('"magic_quotes_gpc" must be switched off for CommSy to work correctly. This must be set in php.ini, .htaccess or httpd.conf.', E_USER_ERROR);
}
if ( get_magic_quotes_runtime() ) {
   if ( isPHP5() ) {
      ini_set('magic_quotes_runtime',0);
      if ( get_magic_quotes_runtime() ) {
         include_once('functions/error_functions.php');
         trigger_error('"magic_quotes_runtime" must be switched off for CommSy to work correctly. See "htaccess-dist".', E_USER_ERROR);
      }
   } else {
      include_once('functions/error_functions.php');
      trigger_error('"magic_quotes_runtime" must be switched off for CommSy to work correctly. See "htaccess-dist".', E_USER_ERROR);
   }
}
if ( isPHP5() ) {
   $ini_reg_globals = ini_get('register_globals');
   if ( !empty($ini_reg_globals) and strtolower($ini_reg_globals) != 'off' ) {
      include_once('functions/error_functions.php');
      trigger_error('"register_globals" must be switched off for CommSy to work correctly. This must be set in php.ini, .htaccess or httpd.conf.', E_USER_ERROR);
   }
}

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();
$class_factory = $environment->getClassFactory();

// transform POST_VARS and GET_VARS --- move into page object, if exist
include_once('functions/text_functions.php');
$_POST = encode(FROM_FORM,$_POST);
$_GET  = encode(FROM_GET,$_GET);
$_GET  = encode(FROM_FORM,$_GET);

// multi master implementation (06.09.2012 IJ)
$db = $environment->getConfiguration('db');
if ( count($db) > 1 ) {
	if ( !empty($_COOKIE['db_pid']) ) {
		$environment->setDBPortalID($_COOKIE['db_pid']);
	} elseif ( !empty($_GET['db_pid']) ) {
		$environment->setDBPortalID($_GET['db_pid']);
	} elseif ( !empty($_POST['db_pid']) ) {
		$environment->setDBPortalID($_POST['db_pid']);
	}
}
// multi master implementation - END

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
   $current_module = 'home';
   $current_function = 'index';
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

// HTML text area corrections
$_POST = $environment->getTextConverter()->correctPostValuesForTextEditor($_POST);

// set output mode: default is html
if ( ($environment->getCurrentFunction() == 'index'
      and $environment->getCurrentModule() == type2Module(CS_MATERIAL_TYPE)
     )
     or $environment->getCurrentModule() == 'ajax'
     or $environment->getCurrentModule() == 'scorm'
   ) {
   if ( !empty($_GET['output']) ) {
      $environment->setOutputMode($_GET['output']);
   } elseif ( !empty($_POST['output']) ) {
      $environment->setOutputMode($_POST['output']);
   }
}

if ( $environment->inPortal()
     or $environment->inServer()
   ) {
   $class_factory->setDesignTo6();
} else {
   $class_factory->setDesignTo7();
}

$server_item = $environment->getServerItem();
if ( $server_item->showOutOfService() ) {
   $current_context_id_save = $environment->getCurrentContextID();
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

if ( !empty($cid_not_set) and $cid_not_set) {

   // check url of portals
   $search_url = '';
   $set_cid = false;
   if ( !empty($_SERVER['HTTP_HOST']) ) {
      $search_url .= $_SERVER['HTTP_HOST'];
      if ( !empty($_SERVER['REQUEST_URI']) ) {
         $search_url .= dirname($_SERVER['REQUEST_URI']);
      }
      if ( substr($search_url,strlen($search_url)-1) == '/' ) {
         $search_url = substr($search_url,0,strlen($search_url)-1);
      }
      $portal_manager = $environment->getPortalManager();
      $portal_manager->setUrlLimit($search_url);
      $portal_manager->select();
      $portal_list = $portal_manager->get();
      if ( !empty($portal_list)
           and $portal_list->isNotEmpty()
         ) {
         $count = $portal_list->getCount();
         if ( $count == 1 ) {
            $portal_item = $portal_list->getFirst();
            if ( isset($portal_item) ) {
               $environment->setCurrentContextID($portal_item->getItemID());
               $set_cid = true;
            }
            unset($portal_item);
         }
         unset($count);
      }
      unset($portal_manager);
      unset($portal_list);

      // check url from server_item
      if ( !$set_cid ) {
         $server_url = $server_item->getURL();
         if ( !empty($server_url)
              and $server_url == $search_url
            ) {
            $environment->setCurrentContextID($server_item->getItemID());
            # don't set cid_set to true, because default portal id should work
         }
      }
   }

   // default portal id
   if ( !$set_cid ) {
      $default_portal_id = $server_item->getDefaultPortalItemID();
      if ( is_numeric($default_portal_id) ) {
         $environment->setCurrentContextID($default_portal_id);
      }
   }
   unset($set_cid);
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
#   if (!isset($_GET['jscheck']) and !isset($_GET['isJS'])){
#       include_once('pages/context_reload.php');
#       exit();
#   }
   include_once('pages/context_login.php');
   exit();
} elseif ( strtolower($environment->getCurrentFunction()) == 'getfile'
           and strtolower($environment->getCurrentModule()) == 'picture'
         ) {
   include_once('pages/picture_getfile.php');
   exit();
} elseif ( strtolower($environment->getCurrentFunction()) == 'getfile'
           and strtolower($environment->getCurrentModule()) == 'individual'
         ) {
   include_once('pages/individual_getfile.php');
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
   if ( isset($session) ) {
      $environment->setSessionItem($session);
   }

   /** password forget (BEGIN) **/
   if ( isset($session)
        and $session->issetValue('password_forget_ip')
        and !( $environment->getCurrentModule() == 'picture'
               and $environment->getCurrentFunction() == 'getfile'
             )
        and !( $environment->getCurrentModule() == 'language'
               and $environment->getCurrentFunction() == 'change'
             )
      ) {
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


/*TYPO3-Anbindung*/
      $typo3_session_id = '';
      if ( !empty($_GET['ses_id']) ) {
         $typo3_session_id = $_GET['ses_id'];
      } elseif ( !empty($_POST['ses_id']) ) {
         $typo3_session_id = $_POST['ses_id'];
      } elseif ( !empty($_COOKIE['ses_id']) ) {
         $typo3_session_id = $_COOKIE['ses_id'];
      }
      if ( !empty($typo3_session_id) ) {
         $get_param_context_id = $environment->getCurrentContextId();
         $environment->setCurrentContextId($environment->getCurrentPortalId());
         $portal = $environment->getCurrentPortalItem();
         $typo3web_list = $portal->getAuthSourceListTypo3WebEnabled();
         if ( $typo3web_list->isNotEmpty() ) {
            $typo3web_auth_source = $typo3web_list->getFirst();
            while ($typo3web_auth_source) {
               $authentication = $environment->getAuthenticationObject();
               $typo3web_manager = $authentication->getAuthManagerByAuthSourceItem($typo3web_auth_source);
               $user_data_array = $typo3web_manager->validateSessionID($typo3_session_id);
               if ( isset($user_data_array['user_id']) and !empty($user_data_array['user_id']) ) {
                  $user_manager = $environment->getUserManager();
                  $auth_source = $typo3web_auth_source->getItemID();
                  $portal_item = $environment->getCurrentPortalItem();
                  if($authentication->exists($user_data_array['user_id'], $auth_source)){
                     $user_manager = $environment->getUserManager();
                     $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
                     $user_manager->setUserIDLimit($user_data_array['user_id']);
                     $user_manager->select();
                     $user_list = $user_manager->get();
                     $user_item = $user_list->getFirst();
                     $environment->setCurrentUser($user_item);
                  } else {
                     $new_account_data = $user_data_array;
                     if ( !empty($new_account_data)
                          and !empty($new_account_data['firstname'])
                          and !empty($new_account_data['lastname'])
                        ) {
                        $user_item = $user_manager->getNewItem();
                        $user_item->setUserID($new_account_data['user_id']);
                        $user_item->setFirstname($new_account_data['firstname']);
                        $user_item->setLastname($new_account_data['lastname']);
                        if(!empty($new_account_data['email'])){
                           $user_item->setEmail($new_account_data['email']);
                        } else {
                           $server_item = $environment->getServerItem();
                           $email = $server_item->getDefaultSenderAddress();
                           $user_item->setEmail($email);
                           $user_item->setHasToChangeEmail();
                        }
                        $user_item->setAuthSource($typo3web_manager->getAuthSourceItemID());
                        $user_item->makeUser();
                        $user_item->save();
                        $environment->setCurrentUser($user_item);
                     }
                  }
                  $user_id = $user_data_array['user_id'];
                  $environment->setCurrentContextId($get_param_context_id);
                  include_once('include/inc_make_session_for_user.php');
                  $session_manager = $environment->getSessionManager();
                  $session_manager->save($session);
                  $environment->setSessionItem($session);

                  $typo3web_manager->sendSessionToTypo3($typo3_session_id, $session->getSessionID());

                  $params = array();
                  $params = $environment->getCurrentParameterArray();
                  unset($params['ses_id']);
                  unset($params['cid']);
                  redirect( $environment->getCurrentContextID(),
                            $environment->getCurrentModule(),
                            $environment->getCurrentFunction(),
                            $params
                          );
                  unset($params);
                  break;
               }
               $typo3web_auth_source = $typo3web_list->getNext();
            }
         }
         unset($portal);
      }
/*Ende TYPO3-Anbindung*/
   }
   
   // commsy: portal2portal
   if ( isset($session)
   	  and $session->issetValue('cookie')
        and $session->getValue('cookie') == 3 // 3 = session made via soap with connection key
   	) {
   	$session_manager = $environment->getSessionManager();
      // save cookie with save session
   	$session_manager->save($session);
   	unset($session_manager);
   }
   // END: commsy: portal2portal

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

      $plugin_boolean_with_check = true;
      if ( $environment->isPlugin($environment->getCurrentModule()) ) {
         $plugin_class = $environment->getPluginClass($environment->getCurrentModule());
         if ( method_exists($plugin_class,'accessPageWithCheck') ) {
            $plugin_boolean_with_check = $plugin_class->accessPageWithCheck($environment->getCurrentFunction());
         }
      }

      if ( !$authentication->check($session->getValue('user_id'),$session->getValue('auth_source'))
           and $environment->getCurrentFunction() != 'logout'
           and $environment->getCurrentFunction() != 'change'
           and $plugin_boolean_with_check
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
         # goto portal "vor die Tuer" - BEGIN
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
      } elseif ( !empty($_GET['login_redirect'])
                 and $_GET['login_redirect']
               ) {
         $history = $session->getValue('history');
         $parameter_array = array();
         if ( !empty($history[0]['parameter']) ) {
            $parameter_array = $history[0]['parameter'];
         }
         $parameter_array['cid'] = $history[0]['context'];
         $parameter_array['mod'] = $history[0]['module'];
         $parameter_array['fct'] = $history[0]['function'];
         unset($parameter_array['login_redirect']);
         $session->setValue('login_redirect',$parameter_array);

         ###############################################
         # goto portal "vor die Tuer" - END
         ###############################################
      }
      $current_user = $authentication->getUserItem();

      // correction of authentication class and got to room door
      if ( $environment->inPortal()
           and !empty($to_room_door)
           and $to_room_door
           and !$current_user->isUser()
           and $environment->getCurrentContextID() == $current_user->getContextID()
           and $current_user->getStatus() != $current_user->getLastStatus()
           and $current_user->getLastStatus() > 1
         ) {
         $current_user->setStatus($current_user->getLastStatus());
      }
      // correction of authentication class and got to room door

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

// multi master implementation - BEGIN
$session_item = $environment->getSessionItem();
if ( $session_item->issetValue('db_save_pid_in_cookie') ) {
	setcookie('db_pid', $environment->getDBPortalID(), 0, $environment->getConfiguration('cookiepath'), $environment->getConfiguration('domain'), 0);
	$session_item->unsetValue('db_save_pid_in_cookie');
} elseif ( $session_item->issetValue('db_renew_pid_in_cookie') ) {
	$cs_pid = 0;
	if ( $environment->inServer()
	     or $environment->inPortal()
	   ) {
		$cs_pid = $environment->getCurrentContextID();
	} else {
		$cs_pid = $environment->getCurrentPortalID();
	}
	setcookie('db_pid', $cs_pid, 0, $environment->getConfiguration('cookiepath'), $environment->getConfiguration('domain'), 0);
	$session_item->unsetValue('db_renew_pid_in_cookie');
}
// multi master implementation - END


/************ language management **************/
$translator = $environment->getTranslationObject();

/************ session: clean search infos *******************/
if ( $environment->getCurrentFunction() == 'index'
     and empty($_POST)
     and !isset($_GET['back_to_search'])
   ) {
   $session = $environment->getSessionItem();
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array')) {
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
   }
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids')) {
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids');
   }
}

/************ profile: update email address *****************/
$current_user_item = $environment->getCurrentUserItem();

/* Typo Login Anbindung*/
global $cs_external_login_redirect;
global $cs_external_login_redirect_exeption_var;
global $cs_external_login_redirect_portal_array;

$current_portal_user_item = null;
if ($current_user_item) {
	$current_portal_user_item = $current_user_item->getRelatedPortalUserItem();
}
if (isset($cs_external_login_redirect) and !empty($cs_external_login_redirect)
	and (!isset($cs_external_login_redirect_exeption_var) or !isset($_GET[$cs_external_login_redirect_exeption_var]) or $_GET[$cs_external_login_redirect_exeption_var] != true)
	and (!isset($current_portal_user_item) or $current_portal_user_item->isGuest())
    and !( $environment->getCurrentModule() == 'context' and $environment->getCurrentFunction() == 'login')
){
	if (isset($cs_external_login_redirect_portal_array) and isset($cs_external_login_redirect_portal_array[0])){
		$pid = $environment->getCurrentPortalID();
		if (in_array($pid,$cs_external_login_redirect_portal_array)){
			$url = $cs_external_login_redirect.$_SERVER['QUERY_STRING'];
	   		header('Location: '.$url);
	   		header('HTTP/1.0 302 Found');
	   		exit();
		}
	}else{
		$url = $cs_external_login_redirect.$_SERVER['QUERY_STRING'];
   		header('Location: '.$url);
   		header('HTTP/1.0 302 Found');
   		exit();
	}
}
/* Ende Typo Login Anbindung*/

$has_to_change_mail = false;
if ( isset($current_user_item) ) {
   $current_portal_user_item = $current_user_item->getRelatedCommSyUserItem();
   if ( isset($current_portal_user_item)
        and $current_portal_user_item->hasToChangeEmail()
      ) {
   	// old profile at portal
      #$_GET['uid'] = $current_user_item->getItemID();
      #$_GET['show_profile'] = 'yes';
      #$_GET['profile_page'] = 'user';
      #$error_message_for_profile_form = $translator->getMessage('COMMON_ERROR_FIELD_CORRECT',$translator->getMessage('USER_EMAIL'));
      
      // new profile at portal
      $has_to_change_mail = true;
      // used at page object      
   }
   unset($current_portal_user_item);
}
unset($current_user_item);

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

/*********** javascript check *************/
if ( !$outofservice
     and $environment->isOutputModeNot('XML')
     and $environment->isOutputModeNot('JSON')
     and $environment->isOutputModeNot('BLANK')
#     and !$environment->getCurrentModule() == 'ajax'
     and !($environment->getCurrentModule() == 'ajax')
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
     and !( $environment->getCurrentModule() == 'context'
            and $environment->getCurrentFunction() == 'login'
          )
   ) {
   include_once('pages/context_reload.php');
   exit();
}
if ( isset($_GET['jscheck'])
     and $environment->isOutputModeNot('XML')
     and $environment->isOutputModeNot('JSON')
     and $environment->isOutputModeNot('BLANK')
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
      }
   }
   if ( isset($session) and !$session->issetValue('https')) {
      if ( isset($_GET['https']) ) {
         if ( !empty($_GET['https']) and $_GET['https'] == 1 ) {
            $session->setValue('https',1);
         } else {
            $session->setValue('https',-1);
         }
      }
   }
   if ( isset($session) and !$session->issetValue('flash')) {
      if (isset($_GET['flash'])) {
         $session->setValue('flash',1);
      } else {
         $session->setValue('flash',-1);
      }
   }
}

$current_user_item = $environment->getCurrentUserItem();

if ( $outofservice
     and $current_user_item->isRoot()
   ) {
   $environment->setCurrentContextID($current_context_id_save);
   $environment->setCurrentModule($current_module_save);
   $environment->setCurrentFunction($current_function_save);
   $current_module = $environment->getCurrentModule();
   $current_function = $environment->getCurrentFunction();
}

/*********** forward ********/
if ( $environment->getCurrentModule() == 'context'
     and  $environment->getCurrentFunction() == 'forward'
     and !$outofservice
   ) {
   include_once('pages/context_forward.php');
   exit();
}

/*********** SMARTY *****************/
global $c_smarty;
if(isset($c_smarty) && $c_smarty === true) {
	require_once('classes/cs_smarty.php');
	global $c_theme;
	$shown_sheme = $c_theme;
	global $theme_array;
	if(!isset($c_theme) || empty($c_theme)) $c_theme = 'default';

	// room theme
	$color = $environment->getCurrentContextItem()->getColorArray();
	$theme = $color['schema'];

	if($theme !== 'default') {
		$correct_theme = false;
		if (is_array($theme_array)){
			foreach($theme_array as $key => $value){
			  if ($theme == $key or $key == 'individual'){
			     $shown_sheme = $key;
			  }
			}
		}
		if($theme == 'individual'){
			 $shown_sheme = $theme;
		}
	}

	$smarty = new cs_smarty($environment, $shown_sheme);

	global $c_smarty_caching;
	if(isset($c_smarty_caching) && $c_smarty_caching === true) {
		$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
	}
	//$smarty->debugging = true;

	// set smarty in environment
	$environment->setTemplateEngine($smarty);

	// set output mode
	if(isset($_GET['mode'])) $environment->setOutputMode($_GET['mode']);


	// determ template
	//$tpl = $environment->getCurrentModule() . '_' . $environment->getCurrentFunction();
}

/*********** PAGE ***********/

global $c_smarty;
$context_item = $environment->getCurrentContextItem();

global $c_smarty_always;
if(isset($c_smarty_always) && $c_smarty_always === true) $c_smarty = true;

if(isset($_GET['smarty'])) {
	if($_GET['smarty'] === 'off') {
		$session->setValue('smarty_off', true);
	} elseif($_GET['smarty'] === 'on') {
		$session->setValue('smarty_off', false);
	}
}

if(isset($_GET['smarty']) || $session->issetValue('smarty_off')) {
	$c_smarty = !$session->getValue('smarty_off');
}

// temporary bypass smarty for server and project context
if($context_item->isServer()
   // or plugins (03.08.2012 IJ)
   or $environment->isPlugin($environment->getCurrentModule())
  ) {
	$c_smarty = false;

} elseif ($context_item->isPortal() && ( empty($_GET["mod"]) or $_GET["mod"] !== "ajax") ) {
	$c_smarty = false;
}

// and anywhere else if conditions match
else {
	if(	$environment->getCurrentFunction() === 'edit' &&
		$environment->getCurrentModule() !== 'discarticle' &&
		$environment->getCurrentModule() !== 'annotation' &&
		$environment->getCurrentModule() !== 'step') {
		$c_smarty = false;
	}

	if($environment->getCurrentModule() === 'discarticle' && $environment->getCurrentFunction() === 'edit' && isset($_POST['option']['new'])) {
		$c_smarty = false;
	}

	if($environment->getCurrentModule() === 'picture') {
		$c_smarty = false;
	}

	if($environment->getCurrentFunction() === 'getfile') {
		$c_smarty = false;
	}

	// show a webpage in a zipfile embedded in texts with (:zip FILENAME:)
	// 03.08.2012 IJ
	if ( $environment->getCurrentFunction() === 'showzip'
	     or $environment->getCurrentFunction() === 'showzip_file'
	   ) {
		$c_smarty = false;
	}
}

if(isset($c_smarty) && $c_smarty === true) {

	/************************************************************************************
	 *** AGB
	************************************************************************************/
	$current_user = $environment->getCurrentUserItem();

	// portal AGB
	$current_context = $environment->getCurrentContextItem();
	if (!$current_context->isPortal() && !$current_context->isServer() && !$current_context->withAGBDatasecurity()) {

		$portal_user = $current_user->getRelatedCommSyUserItem();
		if ( isset($portal_user) and $portal_user->isUser() and !$portal_user->isRoot() ) {
			$current_portal = $environment->getCurrentPortalItem();
			$user_agb_date = $portal_user->getAGBAcceptanceDate();
			$portal_agb_date = $current_portal->getAGBChangeDate();

			if ( $user_agb_date < $portal_agb_date && $current_portal->getAGBStatus() == 1 ) {
				redirect($current_portal->getItemID(), "agb", "detail");
			}
		}
	}

	if ( $current_user->isUser() && !$current_user->isRoot() && !$current_context->withAGBDatasecurity() ) {
		$user_agb_date = $current_user->getAGBAcceptanceDate();
		$context_agb_date = $current_context->getAGBChangeDate();
		if ( $user_agb_date < $context_agb_date && $current_context->getAGBStatus() == 1 ) {
			if ($current_module != "agb"|| $current_function != "index") {
				redirect($current_context->getItemID(), "agb", "index");
			}
		}
	}

	// TODO: get-parameter is checked, because getCurrentModule() returns 'home' when calling 'ajax'
	// TODO: getCurrentFunction() also fails
	if(isset($_GET['mod']) && $_GET['mod'] === 'ajax') {
		$controller_name = 'cs_ajax_' . $_GET['fct'] . '_controller';
		require_once('classes/controller/ajax/' . $controller_name . '.php');

		$controller = new $controller_name($environment);
		$controller->process();
	}elseif(isset($_GET['fct']) && $_GET['fct'] === 'logout') {
		$controller_name = 'cs_context_logout_controller';
		require_once('classes/controller/' . $controller_name . '.php');

		$controller = new $controller_name($environment);
		$controller->process();
	} else {
		if ($environment->getCurrentModule() === "agb" && $environment->getCurrentFunction() === "index") {
			$controller_name = 'cs_agb_controller';
			require_once('classes/controller/' . $controller_name . '.php');
		/*
		if ($showAGB) {
			if ( ($current_module == "picture") && ($current_function == "getfile" || $current_function == "getingray")) {
				include('pages/'.$current_module.'_'.$current_function.'.php');
			} else {
				$controller_name = 'cs_agb_controller';
				require_once('classes/controller/' . $controller_name . '.php');
			}*/
		} elseif(isset($_GET['mod']) && $_GET['mod'] === 'search') {
			$controller_name = 'cs_search_controller';
			require_once('classes/controller/' . $controller_name . '.php');
		} elseif($environment->getCurrentModule() === 'home' || $environment->getCurrentModule() === 'search') {
			$controller_name = 'cs_' . $environment->getCurrentModule() . '_controller';
			require_once('classes/controller/' . $controller_name . '.php');
		} elseif($environment->getCurrentModule() === 'content' && $environment->getCurrentFunction() === 'detail') {
			require_once('pages/content_detail.php');
		}elseif($environment->getCurrentModule() === 'room' && $environment->getCurrentFunction() === 'change') {
			require_once('pages/room_change.php');
		} else {
			$controller_name = 'cs_' . $environment->getCurrentModule() . '_' . $environment->getCurrentFunction() . '_controller';
			require_once('classes/controller/' . $environment->getCurrentFunction() . '/' . $controller_name . '.php');
		}

		if($c_smarty) {
			$controller = new $controller_name($environment);
			$controller->processTemplate();
			$controller->displayTemplate();
		}
	}
} else {
	// with or without modifiying options
	$with_modifying_actions = $context_item_current->isOpen();

	if ( $environment->isOutputMode('XML') ) {
	   $params = array();
	   $params['environment'] = $environment;
	   $params['with_modifying_actions'] = $with_modifying_actions;
	   $page = $class_factory->getClass(PAGE_XML_VIEW,$params);
	   unset($params);
	} elseif ( $environment->isOutputMode('JSON') ) {
	   $params = array();
	   $params['environment'] = $environment;
	   $params['with_modifying_actions'] = $with_modifying_actions;
	   $page = $class_factory->getClass(PAGE_JSON_VIEW,$params);
	   unset($params);
	} elseif ( $environment->isOutputMode('BLANK') ) {
	   $params = array();
	   $params['environment'] = $environment;
	   $params['with_modifying_actions'] = $with_modifying_actions;
	   $page = $class_factory->getClass(PAGE_BLANK_VIEW,$params);
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
	
	// has to change email (new) at portal
	if ( isset($has_to_change_mail)
		  and $has_to_change_mail
		  and isset($page)
		  and method_exists($page, 'setHasToChangeEmail')
	   ) {
		$page->setHasToChangeEmail();
	}

	if ( isset($session) ) {
	   $left_menue_status = $session->getValue('left_menue_status');
	   if ( isset($_GET['left_menue']) and !empty($_GET['left_menue']) ){
	      $session->setValue('left_menue_status', $_GET['left_menue']);
	   }
	}

	if ( $environment->isOutputModeNot('XML') and $environment->isOutputModeNot('JSON') and $environment->isOutputModeNot('BLANK')) {
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
	   #$session->unsetValue('error_array');
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
	if ( $current_context->isLocked()
	     and !( $environment->getCurrentModule() == 'room'
	            and $environment->getCurrentFunction() == 'change'
	          )
	     and !( $environment->getCurrentModule() == 'picture'
	            and $environment->getCurrentFunction() == 'getfile'
	          )
	     and !$current_user->isRoot()
	   ) {
	   $params = array();
	   $params['environment'] = $environment;
	   $params['with_modifying_actions'] = true;
	   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
	   unset($params);
	   if ( $current_context->isPrivateRoom() ) {
	      $room_name = $translator->getMessage('PRIVATEROOM');
	   } else {
	      $room_name = $current_context->getTitle();
	   }
	   $errorbox->setText($translator->getMessage('CONTEXT_IS_LOCKED',$room_name));
	   $page->add($errorbox);
	} elseif ( $current_context->isDeleted()
	           and !( $environment->getCurrentModule() == 'room'
	                  and $environment->getCurrentFunction() == 'change'
	                )
	           and !( $environment->getCurrentModule() == 'picture'
	                  and $environment->getCurrentFunction() == 'getfile'
	                )
	           and !$current_user->isRoot()
	         ) {
	   $params = array();
	   $params['environment'] = $environment;
	   $params['with_modifying_actions'] = true;
	   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
	   unset($params);
	   if ( $current_context->isPrivateRoom() ) {
	      $room_name = $translator->getMessage('PRIVATEROOM');
	   } else {
	      $room_name = $current_context->getTitle();
	   }
	   if ( $current_context->isPortal() ) {
	      $errorbox->setText($translator->getMessage('PORTAL_ERROR_DELETED',$room_name));
	   } else {
	      $errorbox->setText($translator->getMessage('CONTEXT_IS_DELETED',$room_name));
	   }
	   $page->add($errorbox);
	} elseif ( $show_agb_again ) {
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
	   if ( $environment->isPlugin($environment->getCurrentModule()) ) {
	      $current_module = 'plugin';
	      $current_function = 'index';
	      $plugin_module = $environment->getCurrentModule();
	      $plugin_function = $environment->getCurrentFunction();
	   }

	   if ( !file_exists('pages/'.$current_module.'_'.$current_function.'.php') ) {
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
	      include('pages/'.$current_module.'_'.$current_function.'.php');
	   }
	} else {
	   $page->add($errorbox);
	   if ( $environment->inPortal() ) {
	      include('pages/'.$current_module.'_'.$current_function.'.php');
	   }
	}

	if ( $environment->isOutputModeNot('XML') and $environment->isOutputModeNot('JSON') and $environment->isOutputModeNot('BLANK')) {

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
	               case 'ENTRY':
	                  $tempMessage = $translator->getMessage('COMMON_ENTRY_INDEX');
	                  break;
	               default:
	                  $text = '';
	                  if ( $environment->isPlugin($link_name[0]) ) {
	                     $text = plugin_hook_output($link_name[0],'getDisplayName');
	                  }
	                  if ( !empty($text) ) {
	                     $tempMessage = $text;
	                  } else {
	                     $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' '.__FILE__.' ('.__LINE__.') ');
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

	   $password_param = $environment->getValueOfParameter('cs_modus');
	   if ( !empty($password_param)
	        and $password_param == 'password_change'
	      ) {
	      include_once('pages/user_password_overlay.php');
	   }
	}
}

/*********** SMARTY OUTPUT **********/
global $c_smarty;
if(isset($c_smarty) && $c_smarty === true) {
	try {
		//$smarty->display($tpl, $environment->getOutputMode());
	} catch(Exception $e) {
		die($e->getMessage());
	}
} else {

/************************************/

	// display page
	if ( $environment->isOutputMode('XML') or $environment->isOutputMode('JSON') or $environment->isOutputMode('BLANK')) {
	   echo($page->getContent());
	} else {
		header("Content-Type: text/html; charset=utf-8");
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
}

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

// multi master implementation (06.09.2012 IJ)
$db = $environment->getConfiguration('db');
if ( count($db) > 1 ) {
	$session_item = $environment->getSessionItem();
	$cookie = false;
	if ( $session_item->issetValue('cookie')) {
		$cookie_in_session = $session_item->getValue('cookie');
		if ( !empty($cookie_in_session)
		     and $cookie_in_session == 1
		   ) {
			$cookie = true;
		}
	}
	if ( $cookie ) {
		$db_pid = $environment->getDBPortalID();
		if ( $environment->inServer()
		     or $environment->inPortal()
		   ) {
			$cs_pid = $environment->getCurrentContextID();
		} else {
			$cs_pid = $environment->getCurrentPortalID();
		}
		if ( empty($db_pid)
		     or empty($_COOKIE['db_pid'])
		   ) {
			$session_item->setValue('db_save_pid_in_cookie', 1);
		} elseif ( $cs_pid != $db_pid
		           and !empty($_COOKIE['db_pid'])
		         ) {
			$session_item->setValue('db_renew_pid_in_cookie', 1);
		}
	}
}
// multi master implementation - END

/*********** SAVE SESSION ***********/

// save session with history information
// UPDATE: do not store ajax requests in history
// comparison only works with $_GET['mod'] - not with $environment->getCurrentModule()...
if ( $environment->getCurrentFunction() != 'getfile'
     and $environment->getCurrentFunction() != 'getingray'
     and $environment->getCurrentModule() != 'help'
     and (!isset($_GET['mod']) or $_GET['mod'] != 'ajax')
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
include_once('include/inc_log.php');

/*********** ROOM ACTIVITY ***********/
$activity_points = 1;
$context_item_current = $environment->getCurrentContextItem();
if ( isset($context_item_current) ) {
   $context_item_current->saveActivityPoints($activity_points);
   if ( $context_item_current->isProjectRoom()
        or $context_item_current->isCommunityRoom()
        or $environment->inPrivateRoom()
        or $environment->inGroupRoom()
      ) {

   	// archiving
   	$context_item_current->saveLastLogin();

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

if ( isset($c_show_debug_infos) and $c_show_debug_infos and $environment->getCurrentModule() != 'ajax') {

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

   if ($all > 100){
      echo('<div style="color:red; font-weight:bold">Zu viele SQL-Statements ('.$all.'). Grenzwert: 100 Statements</div>');
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

   echo(BRLF);
   echo('<hr/>'.LF);
   echo('<span style="font-weight:bold;">Session Object</span>'.BRLF);
   pr($session);
}
if ( $environment->isOutputModeNot('XML') and $environment->isOutputModeNot('JSON') and $environment->isOutputModeNot('BLANK')) {
   echo('<!-- Total execution time: '.$time.' seconds -->');
}
?>