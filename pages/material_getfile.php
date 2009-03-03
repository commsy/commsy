<?php
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

if ( !empty($_GET['iid']) ) {

   $send_file = false;

   // security
   $current_context_item = $environment->getCurrentContextItem();
   $current_user_item = $environment->getCurrentUserItem();
   if ( $current_user_item->isUser() ) {
      $send_file = true;
   } elseif ( $current_context_item->isOpenForGuests() ) {
      $send_file = true;
      $link_item_file_manager = $environment->getLinkItemFileManager();
      $link_item_file_manager->resetLimits();
      $link_item_file_manager->setFileIdLimit($_GET['iid']);
      $link_item_file_manager->select();
      $link_list = $link_item_file_manager->get();
      $link_item = $link_list->getFirst();
      while ( $link_item ) {
         $linked_item = $link_item->getLinkedItem();
         if ( isset($linked_item)
              and $linked_item->isA(CS_MATERIAL_TYPE)
              and !$linked_item->isDeleted()
              and !$linked_item->isPublished()
            ) {
            $send_file = false;
            break;
         }
         unset($linked_item);
         unset($link_item);
         $link_item = $link_list->getNext();
      }
      unset($link_list);
   } elseif ( $current_context_item->isHomepageLinkActive() ) {
      $link_item_file_manager = $environment->getLinkItemFileManager();
      $link_item_file_manager->resetLimits();
      $link_item_file_manager->setFileIdLimit($_GET['iid']);
      $link_item_file_manager->select();
      $link_list = $link_item_file_manager->get();
      $link_item = $link_list->getFirst();
      while ( $link_item ) {
         $linked_item = $link_item->getLinkedItem();
         if ( isset($linked_item) and $linked_item->isA(CS_HOMEPAGE_TYPE)) {
            $send_file = true;
            break;
         }
         unset($linked_item);
         unset($link_item);
         $link_item = $link_list->getNext();
      }
      unset($link_list);
   }
   unset($current_context_item);
   unset($current_user_item);

   if ( $send_file ) {
         /*********** LOGGING ***********/
      // this has been copied directly from commsy.php
      // to ensure that the download is logged.

      // Log information to database
      $array = array();
      if ( isset($_GET['iid']) ) {
         $array['iid'] = $_GET['iid'];
      } else {
         $array['iid'] = -1;
      }
      if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
         $array['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
      } else {
         $array['user_agent'] = 'No Info';
      }

      $array['remote_addr']      = $_SERVER['REMOTE_ADDR'];
      $array['script_name']      = $_SERVER['SCRIPT_NAME'];
      $array['query_string']     = $_SERVER['QUERY_STRING'];
      $array['request_method']   = $_SERVER['REQUEST_METHOD'];
      $array['user_item_id']     = $current_user->getItemID();
      $array['user_user_id']     = $current_user->getUserID();
      $array['context_id']       = $environment->getCurrentContextID();
      $array['module']           = $current_module;
      $array['function']         = $current_function;
      $array['parameter_string'] = $environment->getCurrentParameterString();

      $log_manager = $environment->getLogManager();
      $log_manager->saveArray($array);
      unset($log_manager);
      unset($iid);

      # File Download
      $file_manager = $environment->getFileManager();
      $file = $file_manager->getItem($_GET['iid']);
      if ( isset($file) ) {
         $file->setContextID($environment->getCurrentContextID());
         if ( $file->isOnDisk() ) {
            header('Content-type: '.$file->getMime());
            // der IE kann damit nicht bei https umgehen, alle anderen Browser schon
            #header('Pragma: no-cache');
            header('Expires: 0');
            @readfile($file->getDiskFileName());
            exit;
         } else {
            include_once('functions/error_functions.php');
            trigger_error("material_getfile: File ".$file->getDiskFileName()." does not seem to be on disk
            <br />environment reports context id ".$environment->getCurrentContextID()."
            <br />file item reports room id ".$file->getContextID()." and context id ".$file->getContextID(), E_USER_ERROR);
         }
         $log_manager = $environment->getLogManager();
         $log_manager->saveArray($array);
         unset($log_manager);
         exit();
      } else {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText(getMessage('FILE_ERROR_GET_FILE_NOT_EXISTS'));
         $page->add($errorbox);
         $page->setWithoutLeftMenue();
      }
   } else {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText(getMessage('FILE_ERROR_GET_FILE'));
      $page->add($errorbox);
      $page->setWithoutLeftMenue();
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error("material_getfile: Have no valid Item ID", E_USER_ERROR);
}
?>