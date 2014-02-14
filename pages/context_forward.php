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
      if ( $session_item->issetValue('https') ) {
         $new_session->setValue('https',$session_item->getValue('https'));
      }
      if ( $session_item->issetValue('flash') ) {
         $new_session->setValue('flash',$session_item->getValue('flash'));
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
      $url .= str_replace($c_single_entry_point,'homepage.php',$_SERVER['PHP_SELF']);
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
      if ( $session_item->issetValue('https') ) {
         $new_session->setValue('https',$session_item->getValue('https'));
      }
      if ( $session_item->issetValue('flash') ) {
         $new_session->setValue('flash',$session_item->getValue('flash'));
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
      $url .= str_replace('homepage.php',$c_single_entry_point,$_SERVER['PHP_SELF']);
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
   $current_user = $environment->getCurrentUserItem();

   if ( $current_user->isUser() ) {
      $current_context = $environment->getCurrentContextItem();

      // save last login
      include_once('functions/date_functions.php');
      $current_user->setLastLoginPlugin(getCurrentDateTimeInMySQL(),'etchat');
      $current_user->setChangeModificationOnSave(false);
      $current_user->save();

      if ( !$environment->inPortal()
           and !$environment->inServer()
         ) {
         $portal_user_item = $current_user->getRelatedCommSyUserItem();
         if ( isset($portal_user_item) ) {
            $portal_user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),'etchat');
            $portal_user_item->setChangeModificationOnSave(false);
            $portal_user_item->save();
            unset($portal_user_item);
         }
      }

      $etchat_manager = $environment->getETChatManager();
      if ( $etchat_manager->insertRoom($current_context) ) {
         $inser_user = $etchat_manager->insertUser($current_user);
         if (!$inser_user) {
            include_once('functions/error_functions.php');
            trigger_error($external_tool.': can not insert user ('.$current_user->getUserID().')',E_USER_ERROR);
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
      // da neues Fenster, geht das nicht
      /*
      $session_item = $environment->getSessionItem();
      $cid = $environment->getCurrentContextID();
      $mod = 'home';
      $fct = 'index';
      $params = array();
      if ( !empty($session_item)
           and $session_item->issetValue('history')
         ) {
         $history = $session_item->getValue('history');
         if ( !empty($history[0]['context']) ) {
            $cid = $history[0]['context'];
         }
         if ( !empty($history[0]['module']) ) {
            $mod = $history[0]['module'];
         }
         if ( !empty($history[0]['function']) ) {
            $fct = $history[0]['function'];
         }
         if ( !empty($history[0]['parameter']) ) {
            $params = $history[0]['parameter'];
         }
      }
      redirect($cid,$mod,$fct,$params);
      */

      // also Fehlermeldung
      include_once('functions/error_functions.php');
      trigger_error($external_tool.': user ('.$current_user->getUserID().') can not login as guest',E_USER_ERROR);
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('forward to tool '.$external_tool.' not implemented',E_USER_WARNING);
}