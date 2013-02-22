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
include_once('functions/misc_functions.php');

// start of execution time
$time_start = getmicrotime();

// include base-config
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');

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
   if ( ini_get('register_globals') ) {
      include_once('functions/error_functions.php');
      trigger_error('"register_globals" must be switched off for CommSy to work correctly. This must be set in php.ini, .htaccess or httpd.conf.', E_USER_ERROR);
   }
}

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();
$translator = $environment->getTranslationObject();
$class_factory = $environment->getClassFactory();

// transform POST_VARS and GET_VARS --- move into page object, if exist
include_once('functions/text_functions.php');
$_POST = encode(FROM_FORM,$_POST);
$_GET  = encode(FROM_FORM,$_GET);

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
if (!isset($_GET['cid'])) {
   $current_context = $environment->getServerID();
   $cid_not_set = true;
} else {
   $current_context = $_GET['cid'];
}

if ( !isset($_GET['mod']) ) {
   $current_module = 'homepage';
} elseif ( isset($_GET['mod']) ) {
   $current_module = $_GET['mod'];
}

if ( $current_module != 'homepage'
     and $current_module != 'picture'
     and $current_module != 'context'
   ) {
   $current_module = 'homepage';
}

if ( !isset($_GET['fct']) ) {
   if ( $current_context != $environment->getServerID() ) {
           $current_function = 'detail';
   } else {
           $current_function = 'index';
   }
} elseif ( isset($_GET['fct']) ) {
   $current_function = $_GET['fct'];
}

if ( $current_function != 'index'
     and $current_function != 'detail'
     and $current_function != 'edit'
     and $current_function != 'getfile'
     and $current_function != 'move'
     and $current_function != 'forward'
     and $current_function != 'logout'
     and $current_function != 'login'
   ) {
   $current_function = 'detail';
}

$environment->setCurrentContextID($current_context);
$environment->setCurrentModule($current_module);
$environment->setCurrentFunction($current_function);
unset($current_context);
unset($current_module);
unset($current_function);
$context_item_current = $environment->getCurrentContextItem();

if ( $environment->inPortal()
     or $environment->inServer()
   ) {
   $class_factory->setDesignTo6();
} else {
   $class_factory->setDesignTo7();
}

/*********** SERVER INITIALIZATION AND JUMP TO HOMEPAGE INDEX ***********/

// send user to ...
// homepage overview, if cid in URL was empty or current context does not exist
if ( $cid_not_set ) {
   // redirect to module = homepage; function = index
}


/*********** SESSION AND AUTHENTICATION ***********/

// get Session ID (SID)
if (!empty($_COOKIE['SID_homepage'])) {
   $SID = $_COOKIE['SID_homepage'];         // session id in a cookie
} elseif (!empty($_GET['SID'])) {
   $SID = $_GET['SID'];                     // session id via GET-VARS (url)
} elseif (!empty($_POST['SID'])) {
   $SID = $_POST['SID'];                    // session id via POST-VARS (form posts)
} else {
   // no session created
   // so create session and redirect to requested page
   $session = new cs_session_item();
   $session->createSessionID('guest');
   $session->setToolName('homepage');

   // external reload to check javascript
   $session_manager = $environment->getSessionManager();
   $session_manager->save($session);
   include_once('pages/context_reload.php');
   exit();
}

if (!empty($SID)) {
   // there is a session_id,
   // and there must be a session,
   // so we can load the session information
   $session_manager = $environment->getSessionManager();
   $session = $session_manager->get($SID);
   if ($session->issetValue('user_id')) {       // session is in database, so session is valid and user has already logged on
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

      if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
         $portal_id = $environment->getCurrentPortalID();
      } else {
         $portal_id = $environment->getCurrentContextID();
      }

      if ($session_commsy_id != $portal_id and
          $session->getValue('user_id') != 'guest' and
          $session->getValue('user_id') != 'root' and
                  $environment->getCurrentFunction() != 'getfile') {
         redirect($session_commsy_id,'homepage','index');
      }

      $authentication = $environment->getAuthenticationObject();
      $authentication->setModule($environment->getCurrentModule());
      $authentication->setFunction($environment->getCurrentFunction());
      // check, if user is allowed here in this context (no password uid evaluation)
      // and set current user
      if ( !$authentication->check( $session->getValue('user_id'), $session->getValue('auth_source') )
            and $environment->getCurrentFunction() !='logout'
         ) {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $errorbox_left = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $error_array = $authentication->getErrorArray();
         if (!empty($error_array)) {
            $error_string = implode('<br />',$error_array);
            $errorbox->setText($error_string);
         } else {
            $errorbox->setText($translator->getMessage('COMMON_ERROR'));
         }
      }
      $current_user = $authentication->getUserItem();
      $environment->setCurrentUserItem($current_user);
   } else {
      // there is no user id in the session information,
      // so delete session and just turn to the beginning of the process
      // a new unknown user comes to a commsy page ...
      $session_manager->delete($SID,true);
      $session->reset(); // so session will not saved at redirect
      $url = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
      if (mb_stristr($url,'&SID=')) {
         $url = mb_substr($url,0,mb_strpos($url,'&SID='));
      }
      redirect_with_url($url);
   }
}

/*********** javascript check *************/
if ( isset($_GET['jscheck']) and empty($_POST) ) {
   $session = $environment->getSessionItem();
   if ( isset($session) and !$session->issetValue('javascript')) {
      if (isset($_GET['isJS'])) {
         $session->setValue('javascript',1);
      } else {
         $session->setValue('javascript',-1);
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
}

/*********** PAGE ***********/
$translator = $environment->getTranslationObject();
// with or without modifiying options
$with_modifying_actions = $context_item_current->isOpen();

// create page object
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $with_modifying_actions;
$page = $class_factory->getClass(PAGE_HOMEPAGE_VIEW,$params);
unset($params);
$page->setCurrentUser($environment->getCurrentUserItem());

// set title
$title = $context_item_current->getTitle();
if ($context_item_current->isProjectRoom() and $context_item_current->isTemplate()) {
   $title .= ' ('.$translator->getMessage('PROJECTROOM_TEMPLATE').')';
} elseif ($context_item_current->isClosed()) {
   $title .= ' ('.$translator->getMessage('PROJECTROOM_CLOSED').')';
}
$page->setRoomName($title);
$page->setPageName($translator->getMessage('HOMEPAGE_PAGETITLE_COMMON'));

// display login errors
if ( isset($session) and $session->issetValue('error_array') ) {
   if ( !isset($errorbox) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $with_modifying_actions;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
   }
   $errorbox->setText(implode('<br/>',$session->getValue('error_array')));
   $session->unsetValue('error_array');
}

// errorbox or include page
if (!isset($errorbox))
{
   // JCommSy delegation
   if ( isset($jcommsy)
        and array_key_exists($environment->getCurrentModule(),$jcommsy)
        and $jcommsy[$environment->getCurrentModule()]
        and ( !isset($_GET['mode'])  or ( isset($_GET['mode'])  and $_GET['mode'] != 'detailattach' ) )
        and ( !isset($_POST['mode']) or ( isset($_POST['mode']) and $_POST['mode']!= 'detailattach' ) )
        and ( !isset($_GET['mode'])  or ( isset($_GET['mode'])  and $_GET['mode'] != 'print' ) )
        and ( !isset($_POST['mode']) or ( isset($_POST['mode']) and $_POST['mode']!= 'print' ) )
        and isset($java_enabled_for)
        and ( in_array( $environment->getCurrentContextId(),$java_enabled_for )
              or in_array( $environment->getCurrentPortalId(),$java_enabled_for )
            )
      ) {
      $param = parameterString($_GET,$_POST);
      header("Location: http://".$jcommsy['Servlet-URL'].'?mod='.$current_module.'&fct='.$environment->getCurrentFunction().$param);
   }
   elseif ( !file_exists('pages/'.$environment->getCurrentModule().'_'.$environment->getCurrentFunction().'.php') )
   {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $with_modifying_actions;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText('The page '.$environment->getCurrentModule().'_'.$environment->getCurrentFunction().' cannot be found!');
      $page->addErrorView($errorbox);

      $current_user = $environment->getCurrentUserItem();
      if ( !isset($current_user) or $current_user->getUserID() == '' )
          {
         $page->setWithoutPersonalArea();
      }
   }
   else
   {
      include('pages/'.$environment->getCurrentModule().'_'.$environment->getCurrentFunction().'.php');
   }
}
else
{
   $page->addErrorView($errorbox);
}

// display page
echo($page->asHTML());
flush();

/*********** SAVE SESSION ***********/

// save session with history information
if ( $environment->getCurrentFunction()   != 'getfile'
     and $environment->getCurrentModule() != 'help'
     and !empty($session)
   ) {
   $history = $session->getValue('history');
   $current_page['context']   = $environment->getCurrentContextID();
   $current_page['module']    = $environment->getCurrentModule();
   $current_page['function']  = $environment->getCurrentFunction();
   $current_page['parameter'] = $environment->getCurrentParameterArray();
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
   $session->setValue('history',$history);
   // kann sich die session nicht selbst speichern ???
   $session_manager = $environment->getSessionManager();
   $session_manager->save($session);
}

/*********** EXECUTION TIME ***********/

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo('<!-- Total execution time: '.$time.' seconds -->'.LF);
?>