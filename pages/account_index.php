<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

include_once('classes/cs_account_index_view.php');
include_once('classes/cs_list.php');

// check, if context is open
$current_room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();

if ( isset($current_room_item) and !$current_room_item->isOpen() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $current_room_item->getTitle()));
   $page->add($errorbox);
   $error = 'true';
} elseif ( !$current_user->isModerator() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,true);
   if ( $current_user->isGuest() ) {
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   } else {
      $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   }
   $page->add($errorbox);
   $error = 'true';
}

if (!isset($error) or !$error) {

   // Find current browsing starting point
   if ( isset($_GET['from']) ) {
      $from = $_GET['from'];
   }  else {
      $from = 1;
   }

   // Find current browsing interval
   // The browsing interval is applied to all rubrics!
   if ( isset($_GET['interval']) ) {
      $interval = $_GET['interval'];
   } elseif ( $session->issetValue('cid'.$environment->getCurrentContextID().'_index_interval') ) {
      $interval = $session->getValue('cid'.$environment->getCurrentContextID().'_index_interval');
   } else {
      $interval = CS_LIST_INTERVAL;
   }

   // Find current sort key
   if ( isset($_GET['sort']) ) {
      $sort = $_GET['sort'];
   }  else {
      $sort = 'name';
   }

   // Find current option
   if ( isset($_POST['option']) ) {
      $option = $_POST['option'];
   } elseif ( isset($_GET['option']) ) {
      $option = $_GET['option'];
   } else {
      $option = '';
   }

   // Search / Select Area
   if ( isOption($option,getMessage('COMMON_RESET')) ) {
      $search = '';
      $selstatus = '';
   } else {

      // Find current search text
      if ( isset($_POST['search']) ) {
         $search = $_POST['search'];
         $from = 1;
      } elseif ( isset($_GET['search']) ) {
         $search = $_GET['search'];
      } else {
         $search = '';
      }

      // Find current status selection
      if ( isset($_POST['selstatus']) ) {
         $selstatus = $_POST['selstatus'];
         $from = 1;
      } elseif ( isset($_GET['selstatus']) ) {
         $selstatus = $_GET['selstatus'];
      } else {
         $selstatus = 7; // 7 = no limit = all accounts
      }

      // Find current auth source selection
      if ( isset($_POST['sel_auth_source']) ) {
         $sel_auth_source = $_POST['sel_auth_source'];
         $from = 1;
      } elseif ( isset($_GET['sel_auth_source']) ) {
         $sel_auth_source = $_GET['sel_auth_source'];
      } else {
         $sel_auth_source = -1; // -1 = no limit = all auth sources
      }
   }

   // Find current mode
   if ( isset($_GET['mode']) ) {
      $mode = $_GET['mode'];
   } elseif (isset($_POST['mode'])) {
      $mode = $_POST['mode'];
   } else {
      $mode = '';
   }

   // Get data from database
   $user_manager = $environment->getUserManager();
   $user_manager->reset();
   $user_manager->setContextLimit($environment->getCurrentContextID());
   $count_all = $user_manager->getCountAll();

   if ( !empty($sort) ) {
      $user_manager->setSortOrder($sort);
   }
   if ( !empty($search) ) {
      $user_manager->setSearchLimit($search);
   }
   if ( !empty($sel_auth_source)
        and  $sel_auth_source != -1
      ) {
      $user_manager->setAuthSourceLimit($sel_auth_source);
   }
   if ( !empty($selstatus) ) {
      if ($selstatus == 10) {
         $user_manager->setContactModeratorLimit();
      } elseif ($selstatus == 21) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setModeratorLimit();
         $user_manager->setCommunityLimit();
      } elseif ($selstatus == 22) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setContactModeratorLimit();
         $user_manager->setCommunityLimit();
      } elseif ($selstatus == 23) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setModeratorLimit();
         $user_manager->setProjectLimit();
      } elseif ($selstatus == 24) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setContactModeratorLimit();
         $user_manager->setProjectLimit();
      } elseif ($selstatus == 25) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setModeratorLimit();
         $user_manager->setCommunityLimit();
         $user_manager->setProjectLimit();
      } elseif ($selstatus == 26) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setContactModeratorLimit();
         $user_manager->setCommunityLimit();
         $user_manager->setProjectLimit();
      } elseif ($selstatus == 31) {
         $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
         $user_manager->setNoMembershipLimit();
      } else {
         $user_manager->setStatusLimit($selstatus);
      }
   }
   $ids = $user_manager->getIDArray();       // returns an array of item ids
   $count_all_shown = count($ids);
   if ( $interval > 0 ) {
      $user_manager->setIntervalLimit($from-1,$interval);
   }
   $user_manager->select();
   $list = $user_manager->get();        // returns a cs_list of user_items

   // Prepare view object
   $view = new cs_account_index_view($environment,$current_room_item->isOpen());

   // Set data for view
   $view->setList($list);
   $view->setCountAll($count_all);
   $view->setCountAllShown($count_all_shown);
   $view->setFrom($from);
   $view->setInterval($interval);
   $view->setSortKey($sort);
   $view->setSearchText($search);
   $view->setSelectedStatus($selstatus);
   $view->setSelectedAuthSource($sel_auth_source);
   $view->setHasCheckboxes('list_actions');


   ////////////////////////////////////
   // selected ids - only for admins //
   ////////////////////////////////////

   if (true) { // in future needed, when merging account, contact and user
      // initiate selected array of IDs
      $selected_ids = array();
      if ($mode == '') {
         $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      } elseif ($mode == 'list_actions') {
         if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                  '_selected_ids')) {
            $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_selected_ids');
         }
      } elseif ( $mode == 'print' ) {
         $view->setPrintableView();
      } else {
         include_once('functions/error_functions.php');trigger_error('lost mode for list, should be "list_actions" or "print"',E_USER_WARNING);
      }

      // Update attached items from cookie (requires JavaScript in browser)
      if ( isset($_COOKIE['attach']) ) {
         foreach ( $_COOKIE['attach'] as $key => $val ) {
            setcookie ('attach['.$key.']', '', time()-3600);
            if ( $val == '1' ) {
               if ( !in_array($key, $selected_ids) ) {
                  $selected_ids[] = $key;
               }
            } else {
               $idx = array_search($key, $selected_ids);
               if ( $idx !== false ) {
                  unset($selected_ids[$idx]);
               }
            }
         }
      }

      // Update attached items from form post (works always)
      if ( isset($_POST['attach']) ) {
         foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            if ( array_key_exists($shown_key, $_POST['attach']) ) {
               if ( !in_array($shown_key, $selected_ids) ) {
                  $selected_ids[] = $shown_key;
               }
            } else {
               $idx = array_search($shown_key, $selected_ids);
               if ( $idx !== false ) {
                  unset($selected_ids[$idx]);
               }
            }
         }
      }
   }


   ///////////////////////////////////////
   // perform list actions              //
   ///////////////////////////////////////

   //data needed to prevent removing all moderators
   $user_manager->resetLimits();
   $user_manager->setContextLimit($environment->getCurrentContextID());
   $user_manager->setModeratorLimit();
   $moderator_ids = $user_manager->getIds();
   if (!is_array($moderator_ids)) {
      $moderator_ids = array();
   }
   $selected_moderator_count = count(array_intersect($selected_ids,$moderator_ids));
   $room_moderator_count = count($moderator_ids);

   if ( isOption($option,getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      $automatic = false;
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'USER_ACCOUNT_DELETE';
            $error = false;
            if ( $room_moderator_count - $selected_moderator_count >= 1 ) {
               $user_manager->resetLimits();
               $array_login_id = array();
               foreach ( $selected_ids as $id ) {
                  $user = $user_manager->getItem($id);
               }
            } else {
               $error = true;
               $error_text_on_selection = getMessage('ERROR_LAST_MODERATOR');
               $action = '';
            }
            break;
         case 2:
            $action = 'USER_ACCOUNT_LOCK';
            if ($room_moderator_count - $selected_moderator_count < 1) {
               $error = true;
               $error_text_on_selection = getMessage('ERROR_LAST_MODERATOR');
               $action = '';
            }
            break;
         case 3:
            $action = 'USER_ACCOUNT_FREE';
            if ($room_moderator_count - $selected_moderator_count < 1) {
               $error = true;
               $error_text_on_selection = getMessage('ERROR_LAST_MODERATOR');
               $action = '';
            }
            break;
         case 4:
            $action = 'USER_ACCOUNT_FREE';
            $automatic = true;
            if ( $room_moderator_count - $selected_moderator_count < 1 ) {
               $error = true;
               $error_text_on_selection = getMessage('ERROR_LAST_MODERATOR');
               $action = '';
            }
            break;
         case 11:
            $action = 'USER_STATUS_USER';
            if ($room_moderator_count - $selected_moderator_count < 1) {
               $error = true;
               $error_text_on_selection = getMessage('ERROR_LAST_MODERATOR');
               $action = '';
            }
            break;
         case 14:
            $action = 'USER_STATUS_MODERATOR';
            break;
         case 21:
            $action = 'USER_EMAIL_SEND';
            break;
         case 22:
            $action = 'USER_EMAIL_ACCOUNT_PASSWORD';
            break;
         case 30:
            $action = 'USER_MAKE_CONTACT_PERSON';
            $error = false;
            $user_manager = $environment->getUserManager();
            $array_login_id = array();
            foreach ($selected_ids as $id) {
               $user = $user_manager->getItem($id);
               if ( !$user->isUser() ) {
                  $error = true;
                  $array_login_id[] = $id;
               }
            }
            if ($error) {
               $error_text_on_selection = getMessage('INDEX_USER_MAKE_CONTACT_ERROR');
               $selected_ids = array_diff($selected_ids,$array_login_id);
               $view->setCheckedIDs($selected_ids);
            }
            break;
         case 31:
            $action = 'USER_UNMAKE_CONTACT_PERSON';
            $error = false;
            $user_manager = $environment->getUserManager();
            $array_login_id = array();
            foreach ($selected_ids as $id) {
               $user = $user_manager->getItem($id);
               if ( !$user->isContact() ) {
                  $error = true;
                  $array_login_id[] = $id;
               }
            }
            if ($error) {
               $error_text_on_selection = getMessage('INDEX_USER_UNMAKE_CONTACT_ERROR');
               $selected_ids = array_diff($selected_ids,$array_login_id);
               $view->setCheckedIDs($selected_ids);
            }
            break;
         case 23:
            $action = 'USER_EMAIL_ACCOUNT_MERGE';
            $user_manager = $environment->getUserManager();
            $array_double_id = array();
            foreach ($selected_ids as $id) {
               if (!in_array($id,$array_double_id)) {
                  $user = $user_manager->getItem($id);
                  if ($user->isRejected() or $user->isRequested()) {
                     $array_double_id[] = $user->getItemID();
                  } else {
                     $user_manager->resetLimits();
                     $user_manager->setContextLimit($environment->getCurrentContextID());
                     $user_manager->setUserLimit();
                     $user_manager->setSearchLimit($user->getEmail());
                     $user_manager->select();
                     $user_list = $user_manager->get();
                     if (!$user_list->isEmpty()) {
                        if ($user_list->getCount() > 1) {
                           $user_item = $user_list->getFirst();
                           while ($user_item) {
                              if ($user_item->getItemID() != $id and in_array($user_item->getItemID(),$selected_ids)) {
                                 $array_double_id[] = $user_item->getItemID();
                              }
                              $user_item = $user_list->getNext();
                           }
                        } else {
                           $array_double_id[] = $id;
                        }
                     } else {
                        include_once('functions/error_functions.php');
                        trigger_error('that is impossible',E_USER_WARNING);
                     }
                  }
               }
            }
            if (!empty($array_double_id)) {
               $array_double_id = array_unique($array_double_id);
               $selected_ids = array_diff($selected_ids,$array_double_id);
            }
            if (empty($selected_ids)) {
               $error = true;
               $error_text_on_selection = getMessage('INDEX_USER_ACCOUNT_MERGE_ERROR');
               $view->setCheckedIDs($selected_ids);
            }
            break;
         default:
            include_once('functions/error_functions.php');
            trigger_error('action ist not defined',E_USER_ERROR);
      }
      if (!isset($error) or !$error) {
         $current_user = $environment->getCurrentUser();
         $user_item_id = $current_user->getItemID();

         $action_array = array();
         $action_array['user_item_id']    = $user_item_id;
         $action_array['action']          = $action;
         $action_array['backlink']['cid'] = $environment->getCurrentContextID();
         $action_array['backlink']['mod'] = $environment->getCurrentModule();
         $action_array['backlink']['fct'] = $environment->getCurrentFunction();
         $action_array['backlink']['par'] = '';
         $action_array['selected_ids']    = $selected_ids;
         $params = array();
         $session->setValue('index_action',$action_array);
         redirect( $environment->getCurrentContextID(),
                   $environment->getCurrentModule(),
                   'action',
                   $params);

      } // end if of $error
   } // end if (perform list actions)


   ///////////////////////////////////////
   // show page                         //
   ///////////////////////////////////////

   // error on list view actions
   $error_text = '';
   if (!empty($error_text_on_selection)) {
      $error_text .= $error_text_on_selection;
   }
   if (!empty($error_text)) {
      include_once('classes/cs_errorbox_view.php');
      $errorbox = new cs_errorbox_view($environment, true, 500);
      $errorbox->setText($error_text);
      $page->add($errorbox);
   }

   // Add list view to page
   if (isset($_GET['select']) and $_GET['select']=='all'){
      $item = $list->getFirst();
      while($item){
         if ( !in_array($item->getItemID(), $selected_ids) ) {
            $selected_ids[] = $item->getItemID();
         }
         $item = $list->getNext();
      }
   }
   if (true) { // in future needed, when merging account, contact and user
      $view->setCheckedIDs($selected_ids);
   }
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addConfigurationListView($view);
   }else{
      $page->add($view);
   }

   // Safe information in session for later use
   $session->setValue('cid'.$environment->getCurrentContextID().'_index_interval', $interval); // interval is applied to all rubrics
   $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids', $ids);
   if (true) { // in future needed, when merging account, contact and user
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
   }
}
?>