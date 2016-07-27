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

// CommSy-Plugin logout-hook
plugin_hook('logout');

// delete session
$session = $environment->getSessionItem();
$history = $session->getValue('history');
$cookie = $session->getValue('cookie');
$javascript = $session->getValue('javascript');
$https = $session->getValue('https');
$flash = $session->getValue('flash');
if ( $session->issetValue('root_session_id') ) {
   $root_session_id = $session->getValue('root_session_id');
}

global $symfonyContainer;
$shib_redirect_url = $symfonyContainer->getParameter('commsy.login.shibboleth_redirect_url');
$shib_direct_login = $symfonyContainer->getParameter('commsy.login.shibboleth_direct_login');

if ($shib_direct_login and !empty($shib_redirect_url)){
	if ($_SERVER['Shib_userId']){
		$session_manager->delete($SID,true);
		$session->reset();
		redirect_with_url($shib_redirect_url);
	}
} else {
	$session_manager->delete($SID,true);
	$session->reset();
}


setcookie("expired_password_shown", null);

include_once('classes/cs_session_item.php');
$session = new cs_session_item();
$session->createSessionID('guest');
if ($cookie == '1') {
   $session->setValue('cookie',2);
} else {
   $session->setValue('cookie',0);
}
if ($javascript == '1') {
   $session->setValue('javascript',1);
} elseif ($javascript == '-1') {
   $session->setValue('javascript',-1);
}
if ($https == '1') {
   $session->setValue('https',1);
} elseif ($https == '-1') {
   $session->setValue('https',-1);
}
if ($flash == '1') {
   $session->setValue('flash',1);
} elseif ($flash == '-1') {
   $session->setValue('flash',-1);
}

if ( !empty($_GET['back_tool']) ) {
   $back_tool = $_GET['back_tool'];
   $back_file = $back_tool.'.php';
} else {
   $back_tool = '';
   $back_file = '';
}

$environment->setSessionItem($session);

// redirect
$current_context = $environment->getCurrentContextItem();
if ( isset($root_session_id) and !empty($root_session_id) ) {
   // change cookie
   if ( $cookie == '1' ) {
      $session_manager = $environment->getSessionManager();
      $session = $session_manager->get($root_session_id);
      $session->setValue('cookie',2);
      unset($session_manager);
      $environment->setSessionItem($session);
   }
   $params = $history[0]['parameter'];
   $params['SID'] = $root_session_id;
   redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params,'','',$back_tool);
} elseif ( !$current_context->isOpenForGuests()
           and ( empty($back_tool)
                 or ( !empty($back_tool)
                      and $back_tool == 'commsy'
                    )
               )
         ) {
   if (!$current_context->isServer()) {
      $parent_context = $current_context->getContextItem();
      if ($parent_context->isOpenForGuests()) {
         if ($parent_context->isPortal()) {
            $params = array();
            $params['room_id'] = $current_context->getItemID();
            if ( $current_context->isGroupRoom() ) {
               $project_room_item_id = $current_context->getLinkedProjectItemID();
               if ( !empty($project_room_item_id) ) {
                  $params['room_id'] = $project_room_item_id;
               }
            }
            redirect($parent_context->getItemID(),'home','index',$params,'','',$back_tool);
            unset($params);
         } else {
            redirect($parent_context->getItemID(),'home','index','','','',$back_tool);
         }
      }
   } else {
      redirect($current_context->getItemID(),'home','index','','','',$back_tool);
   }
} else {
   redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter'],'','',$back_tool);
}
$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
redirect_with_url($url);
?>