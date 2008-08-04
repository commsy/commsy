<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if (!empty($_GET['back_tool'])) {
   $back_file = $_GET['back_tool'].'.php';
} else {
   $back_file = '';
}

// case: login with CommSy
if ( isset($session) ) {
   $history = $session->getValue('history');
   $cookie = $session->getValue('cookie');
   $javascript = $session->getValue('javascript');
}
// case: login with external login box
else {
   $history = array();
   $cookie = '';
   $javascript = '';
}

if (!empty($_POST['user_id']) and !empty($_POST['password']) ) {
   $authentication = $environment->getAuthenticationObject();
   if ( isset($_POST['auth_source']) and !empty($_POST['auth_source']) ) {
      $auth_source = $_POST['auth_source'];
   } else {
      $auth_source = '';
   }
   if ($authentication->isAccountGranted($_POST['user_id'],$_POST['password'],$auth_source)) {
      $session = new cs_session_item();
      $session->createSessionID($_POST['user_id']);
      #if ( !empty($_SERVER['SERVER_ADDR']) ) {
      #   $session->setValue('IP',$_SERVER['SERVER_ADDR']);
      #}
      if ( $cookie == '1' ) {
         $session->setValue('cookie',2);
      } elseif ( empty($cookie) ) {
         // do nothing, so CommSy will try to save cookie
      } else {
         $session->setValue('cookie',0);
      }
      if ($javascript == '1') {
         $session->setValue('javascript',1);
      } elseif ($javascript == '-1') {
         $session->setValue('javascript',-1);
      }

      // save portal id in session to be sure, that user didn't
      // switch between portals
      if ( $environment->inServer() ) {
         $session->setValue('commsy_id',$environment->getServerID());
      } else {
         $session->setValue('commsy_id',$environment->getCurrentPortalID());
      }

      // external tool
      if ( stristr($_SERVER['PHP_SELF'],'homepage.php') ) {
         $session->setToolName('homepage');
      }

      // auth_source
      if ( empty($auth_source) ) {
         $auth_source = $authentication->getAuthSourceItemID();
      }
      $session->setValue('auth_source',$auth_source);

   } else {
      $error_array = $authentication->getErrorArray();
      if ( !isset($session) ) {
         $session = new cs_session_item();
         $session->createSessionID('guest');
      }
      $session->setValue('error_array',$error_array);
   }
} elseif ( empty($_POST['user_id']) or empty($_POST['password']) ) {
   $translator = $environment->getTranslationObject();
   $error_array = array();
   if ( empty($_POST['user_id']) ) {
      $error_array[] = $translator->getMessage('COMMON_ERROR_FIELD',$translator->getMessage('COMMON_ACCOUNT'));
   }
   if ( empty($_POST['password']) ) {
      $error_array[] = $translator->getMessage('COMMON_ERROR_FIELD',$translator->getMessage('COMMON_PASSWORD'));
   }
   if ( !isset($session) ) {
      $session = new cs_session_item();
      $session->createSessionID('guest');
   }
   $session->setValue('error_array',$error_array);
}

// redirect
if (!empty($_GET['target_cid'])) {
   $mod = 'home';
   $fct = 'index';
   $params = array();
   redirect($_GET['target_cid'],$mod,$fct,$params);
} else {
   $mod = $history[0]['module'];
   $fct = $history[0]['function'];
   $params = $history[0]['parameter'];
   if ( isset($error_array) and !empty($error_array) ) {
      if ( isset($auth_source) and !empty($auth_source) ) {
         $params['auth_source'] = $auth_source;
      }
   }
   redirect($history[0]['context'],$mod,$fct,$params,'','',$back_file);
}
?>