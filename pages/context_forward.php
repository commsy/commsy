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

if ( !isset($_GET['tool']) and !empty($_GET['tool']) ) {
   include_once('functions/error_functions.php');
   trigger_error('lost external tool',E_USER_WARNING);
} else {
   $external_tool = $_GET['tool'];
}

if ( $external_tool == 'homepage' ) {

   // session
   $session_item = $environment->getSessionItem();
   include_once('classes/cs_session_item.php');
   $new_session = new cs_session_item();
   $current_user = $environment->getCurrentUserItem();
   $user_id = $current_user->getUserID();
   if ( mb_strtoupper($user_id, 'UTF-8') == 'GUEST'
        or mb_strtoupper($user_id, 'UTF-8') == 'ROOT'
      ) {
      $new_session->createSessionID($user_id);
   } elseif ( isset($session_item) ) {
      if ( $session_item->issetValue('auth_source') ) {
         $current_context = $environment->getCurrentContextItem();
         if ( $current_context->mayEnterByUserID($user_id,$session_item->getValue('auth_source')) ) {
            $new_session->createSessionID($user_id);
         } else {
            $new_session->createSessionID('GUEST');
         }
      } else {
         trigger_error('lost auth source',E_USER_ERROR);
      }
   } else {
      $new_session->createSessionID('GUEST');
   }
   $new_session->setValue('commsy_id',$environment->getCurrentPortalID());
   $new_session->setToolName($external_tool);
   $new_session->setValue('commsy_session_id',$session_item->getSessionID());
   if ( isset($session_item) ) {
      if ( $session_item->issetValue('javascript') ) {
         $new_session->setValue('javascript',$session_item->getValue('javascript'));
      }
      if ( $session_item->issetValue('auth_source') ) {
         $new_session->setValue('auth_source',$session_item->getValue('auth_source'));
      }
      if ( $session_item->issetValue('cookie') ) {
         $cookie = $session_item->getValue('cookie');
         if ( $cookie == '0' ) {
            $new_session->setValue('cookie','0');
         } elseif ( !empty($cookie) ) {
            $new_session->setValue('cookie','2');
         }
      }
   }
   $session_manager = $environment->getSessionManager();
   $session_manager->save($new_session);

   // redirect
   $url = 'http://';
   $url .= $_SERVER['HTTP_HOST'];
   $pos = mb_strpos($_SERVER['PHP_SELF'],'?');
   if (!$pos) {
      $url .= str_replace('commsy.php','homepage.php',$_SERVER['PHP_SELF']);
   } else {
      $url .= mb_substr($_SERVER['PHP_SELF'],0,$pos-1);
   }
   $url .= '?cid='.$environment->getCurrentContextID().'&fct=detail';
   if ( !isset($cookie) or $cookie != '1') {
      $url .= '&SID='.$new_session->getSessionID();
   }
   include_once('functions/misc_functions.php');
   redirect_with_url($url);

} elseif ( $external_tool == 'commsy' ) {

   // session
   $session_item = $environment->getSessionItem();
   include_once('classes/cs_session_item.php');
   $new_session = new cs_session_item();
   $current_user = $environment->getCurrentUserItem();
   $new_session->createSessionID($current_user->getUserID());
   $new_session->setValue('commsy_id',$environment->getCurrentPortalID());
   $new_session->setToolName($external_tool);
   if ( isset($session_item) ) {
      if ( $session_item->issetValue('javascript') ) {
         $new_session->setValue('javascript',$session_item->getValue('javascript'));
      }
      if ( $session_item->issetValue('auth_source') ) {
         $new_session->setValue('auth_source',$session_item->getValue('auth_source'));
      }
      if ( $session_item->issetValue('cookie') ) {
         $cookie = $session_item->getValue('cookie');
         if ($cookie == 0) {
            $new_session->setValue('cookie','0');
         } elseif ( !empty($cookie) ) {
            $new_session->setValue('cookie','2');
         }
      }
   }
   $session_manager = $environment->getSessionManager();
   $session_manager->save($new_session);

   // redirect
   $url = 'http://';
   $url .= $_SERVER['HTTP_HOST'];
   $pos = mb_strpos($_SERVER['PHP_SELF'],'?');
   if (!$pos) {
      $url .= str_replace('homepage.php','commsy.php',$_SERVER['PHP_SELF']);
   } else {
      $url .= mb_substr($_SERVER['PHP_SELF'],0,$pos-1);
   }
   $url .= '?cid='.$environment->getCurrentContextID().'&mod=home&fct=index';
   $url .= '&SID='.$new_session->getSessionID();
   include_once('functions/misc_functions.php');
   redirect_with_url($url);

} elseif ( $external_tool == 'etchat'
           and !empty($c_etchat_enable)
           and $c_etchat_enable
         ) {
   $current_context = $environment->getCurrentContextItem();
   $current_user = $environment->getCurrentUserItem();

   $etchat_manager = $environment->getETChatManager();
   if ( $etchat_manager->insertRoom($current_context) ) {
      $inser_user = $etchat_manager->insertUser($current_user);
      if (!$inser_user) {
         pr('CHAT_ERROR');
      } else {
         session_start();
         $_SESSION['user_id'] = $current_user->getItemID();
         $_SESSION['username'] = $current_user->getFullname();
         $_SESSION['user_priv'] = 'gast';
         $_SESSION['room_id'] = $current_context->getItemID();

         $url = $c_etchat_url.'/chat.php?room_id='.$current_context->getItemID();
         include_once('functions/misc_functions.php');
         redirect_with_url($url);
      }
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('forward to tool '.$external_tool.' not implemented',E_USER_WARNING);
}