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

// Get item to be edited
set_time_limit(0);

if ( !empty($_GET['iid']) ) {
   $iid = $_GET['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('lost room id',E_USER_ERROR);
}

if ( !empty($_GET['tid']) ) {
   $tid = $_GET['tid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('lost task id',E_USER_ERROR);
}

if ( !empty($_GET['modus']) ) {
   $modus = $_GET['modus'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('lost modus',E_USER_ERROR);
}

$manager = $environment->getRoomManager();
$item = $manager->getItem($iid);
$current_user = $environment->getCurrentUserItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ( !empty($iid) and !isset($item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $iid));
   $page->add($errorbox);
} elseif ( !$environment->inPortal() or !$current_user->isModerator() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}

// Access granted
else {

   // reject movement of room
   if ( $modus == 'reject' ) {

      // unlock room
      $item->unlockForMove();
      $item->save();

      // close task
      $task_manager = $environment->getTaskManager();
      $task_item = $task_manager->getItem($tid);
      $task_creator = $task_item->getCreatorItem();
      $task_item->setStatus('CLOSED');
      $task_item->save();

      // send email to moderator of other portal
      $portal_manager = $environment->getPortalManager();
      $translator = $environment->GetTranslationObject();
      $portal = $portal_manager->getItem($item->getContextID());
      $language_portal = $portal->getLanguage();
      if ($language_portal == 'user') {
         $language_user = $task_creator->getLanguage();
         if ($language_user == 'browser') {
            $language = $environment->getSelectedLanguage();
         } else {
            $language = $language_user;
         }
      } else {
         $language = $language_portal;
      }

      $translator->setSelectedLanguage($language);
      $current_portal = $environment->getCurrentPortalItem();


      include_once('classes/cs_mail.php');
      $mail = new cs_mail();
      $mail->set_to($task_creator->getEmail());

       global $symfonyContainer;
       $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
       $mail->set_from_email($emailFrom);

      $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
      $mail->set_reply_to_name($current_user->getFullname());
      $mail->set_reply_to_email($current_user->getEmail());
      $mail->set_subject('RE: '.$translator->getMessage('MOVE_ROOM_MAIL_SUBJECT',$portal->getTitle()));
      $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
      $body .= LF.LF;
      $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_REJECT',$item->getTitle());
      $body .= LF.LF;
      $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$portal->getItemID();
      $mail->set_message($body);
      $mail->send();

      $params = array();
      redirect($environment->getCurrentContextID(),'home', 'index', $params);
   }

   // agree movement of room
   elseif ( $modus == 'agree' ) {
      $old_portal_id = $item->getContextID();
      $portal_manager = $environment->getPortalManager();
      $old_portal = $portal_manager->getItem($old_portal_id);

      $user_manager = $environment->getUserManager();

      $copy_links_between_rooms = false;

      // init room list and room array
      include_once('classes/cs_list.php');
      $room_list = new cs_list();
      $room_list->add($item);
      if ($item->moveWithLinkedRooms()) {
         $room_list->addList($item->getProjectRoomList());
         $copy_links_between_rooms = true;

         // add group rooms from project room
         $projectRoomList = $item->getProjectRoomList();
         $projectRoom = $projectRoomList->getFirst();
         while($projectRoom) {
            $room_list->addList($projectRoom->getGroupRoomList());
            $projectRoom = $projectRoomList->getNext();
         }
      }

      ############################################
      # FLAG: group rooms
      ############################################
      elseif ( $item->isGrouproomActive() ) {
         $group_manager = $environment->getGroupManager();
         $group_manager->setContextLimit($item->getItemID());
         $group_manager->select();
         $group_list = $group_manager->get();
         if ( $group_list->isNotEmpty() ) {
            $group_item = $group_list->getFirst();
            while ($group_item) {
               if ( $group_item->isGroupRoomActivated() ) {
                  $grouproom_item = $group_item->getGroupRoomItem();
                  if ( isset($grouproom_item) and !empty($grouproom_item) ) {
                     $room_list->add($grouproom_item);
                  }
               }
               $group_item = $group_list->getNext();
            }
         }
      }
      
      ############################################
      # FLAG: group rooms
      ############################################

      // select user (portal) array
      // and init room array with room titles
      // and init user_link_room_array
      $room_name_array = array();
      $user_array = array();
      $auth_source_array = array();
      $room_item = $room_list->getFirst();
      while ($room_item) {
         $room_name_array[$room_item->getItemID()] = $room_item->getTitle();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($room_item->getItemID());
         $user_manager->select();
         $user_list = $user_manager->get();
         if ($user_list->isNotEmpty()) {
            $user_item = $user_list->getFirst();
            while ($user_item) {
               $auth_source_array[$user_item->getAuthSource()] = $user_item->getAuthSource();
               $user_id_test = $user_item->getUserID();
               if (!empty($user_id_test)) {
                  $user_room_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()][] = $room_item->getItemID();
                  if (empty($user_array[strtoupper($user_item->getUserID())])) {
                     $portal_user_item = $user_item->getRelatedCommSyUserItem();
                     if (isset($portal_user_item)) {
                        $user_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()] = $portal_user_item;
                     }
                  }
                  $user_id_test = $user_item->getUserID();
               }
               $user_item = $user_list->getNext();
            }
         }
         $room_item = $room_list->getNext();
      }

      // AUTH: check auth source compliance
      $auth_source_translation_array = array();
      $auth_source_find_array = array();
      $auth_source_item_array = array();
      $new_portal = $environment->getCurrentContextItem();
      $auth_source_list_new = $new_portal->getAuthSourceList();
      foreach ( $auth_source_array as $auth_source_id ) {
         $auth_source_manager = $environment->getAuthSourceManager();
         $auth_source_item_old = $auth_source_manager->getItem($auth_source_id);
         $auth_source_item_array[$auth_source_item_old->getItemID()] = $auth_source_item_old;
         if ( !$auth_source_list_new->isEmpty() ) {
            $auth_source_item_new = $auth_source_list_new->getFirst();
            while ($auth_source_item_new) {
               $auth_source_item_array[$auth_source_item_new->getItemID()] = $auth_source_item_new;
               if ( $auth_source_item_old->isCommSyDefault()
                    and $auth_source_item_new->isCommSyDefault()
                  ) {
                  $auth_source_translation_array[$auth_source_item_old->getItemID()] = $auth_source_item_new->getItemID();
               }
               if ( $auth_source_item_old->getSourceType() == 'CAS'
                    and $auth_source_item_new->getSourceType() == 'CAS'
                  ) {
                  $auth_data_new = $auth_source_item_new->getAuthData();
                  $auth_data_old = $auth_source_item_old->getAuthData();
                  if ( $auth_data_new['HOST'] == $auth_data_new['HOST']
                       and $auth_data_new['PATH'] == $auth_data_new['PATH']
                     ) {
                     $auth_source_translation_array[$auth_source_item_old->getItemID()] = $auth_source_item_new->getItemID();
                  }
               }
               if ( $auth_source_item_old->getSourceType() == 'LDAP'
                    and $auth_source_item_new->getSourceType() == 'LDAP'
                  ) {
                  $auth_data_new = $auth_source_item_new->getAuthData();
                  $auth_data_old = $auth_source_item_old->getAuthData();
                  if ( $auth_data_new['HOST'] == $auth_data_new['HOST']
                       and $auth_data_new['BASE'] == $auth_data_new['BASE']
                     ) {
                     $auth_source_translation_array[$auth_source_item_old->getItemID()] = $auth_source_item_new->getItemID();
                  }
               }
               $auth_source_item_new = $auth_source_list_new->getNext();
            }
         }
      }
      $auth_source_not_translation_array = array();
      foreach ( $auth_source_array as $auth_source_id ) {
         if ( !array_key_exists($auth_source_id,$auth_source_translation_array) ) {
            $auth_source_not_translation_array[] = $auth_source_id;
         }
      }
      $auth_source_manager = $environment->getAuthSourceManager();
      foreach ( $auth_source_not_translation_array as $auth_source_id ) {
         $auth_source_item_old = $auth_source_manager->getItem($auth_source_id);
         $auth_source_item_new = clone $auth_source_item_old;
         $auth_source_item_new->setItemID('');
         $auth_source_item_new->setContextID($environment->getCurrentContextID());
         $auth_source_item_new->save();
         $auth_source_translation_array[$auth_source_item_old->getItemID()] = $auth_source_item_new->getItemID();
         $auth_source_item_array[$auth_source_item_new->getItemID()] = $auth_source_item_new;
      }

      // save user array for sending emails
      $user_array_all = $user_array;
      $user_array_new = array();

      // select user to copy
      $failure = false;
      $user_change_array = array();
      foreach ($user_array_all as $key => $user_item) {

         // exits user at current portal ?
         $authentication = $environment->getAuthenticationObject();
         $user_id = $user_item->getUserID();
         $auth_source = $user_item->getAuthSource();
         $first = true;
         $go = true;
         while ($go) {
            $user_manager->resetLimits();
            $user_manager->setContextLimit($environment->getCurrentPortalID());
            $user_manager->setAuthSourceLimit($auth_source_translation_array[$auth_source]);
            $user_manager->setUserIDLimit($user_id);
            $user_manager->select();
            $user_list = $user_manager->get();

            // external auth source
            if ( !$auth_source_item_array[$auth_source_translation_array[$auth_source]]->isCommSyDefault() ) {
               unset($user_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()]);
               // user_id exists in portal
               if ( $user_list->isEmpty() ) {
                  $user_array_new[strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$auth_source]] = $user_item;
               }
               $user_array_no_change[strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$auth_source]] = $user_item;
               $go = false;
            }

            // commsy auth source
            // user_id exists in portal
            elseif ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
               $user_item2 = $user_list->getFirst();

               // email is equal
               if ($user_item2->getEmail() == $user_item->getEmail()) {
                  unset($user_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()]);
                  if ($user_item->getUserID() != $user_id) {
                     $user_change_array[strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$auth_source]] = $user_id;
                  } else {
                     $user_array_no_change[strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$auth_source]] = $user_item;
                  }
                  $go = false;
               } else {
                  // generate new user id
                  if ($first) {
                     $first = false;
                     $user_id .= '1';
                  } else {
                     $count = $user_id{mb_strlen($user_id)-1};
                     $count = (int)$count;
                     $count++;
                     $user_id = mb_substr($user_id,0,mb_strlen($user_id)-1);
                     $user_id .= $count;
                  }
               }
            } elseif ($user_list->isNotEmpty() and $user_list->getCount() > 1) {
               include_once('functions/error_functions.php');
               trigger_error('ERROR: multiple user id '.$user_id.' for one portal',E_USER_WARNING);
               $go = false;
               $failure = true;
            } else {
               // find free user id
               if ($user_item->getUserID() != $user_id) {
                  $user_change_array[strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$auth_source]] = $user_id;
               } else {
                  $user_array_no_change[strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$auth_source]] = $user_item;
               }
               $go = false;
            }
         }
      }

      if ($failure) {
         exit();
      }

      // commsy auth source
      // copy auth (user_id and password) and user (normal information) items
      foreach ($user_array as $key => $user_item) {
         $key_array = explode('__CS__',$key);
         $user_id_key = $key_array[0];
         $auth_source_key = $key_array[1]; // old auth source
         $auth_manager = $authentication->getAuthManager($user_item->getAuthSource());
         $auth_manager->setContextLimit($old_portal_id);
         $auth_item_old = $auth_manager->getItem($user_item->getUserID());
         if ( !empty($auth_item_old) ) {
            $auth_item_new = clone $auth_item_old;
            $auth_item_new->setPortalID($environment->getCurrentPortalID());
            $auth_item_new->setAuthSourceID($auth_source_translation_array[$auth_source_key]);
            if (!empty($user_change_array[$user_id_key.'__CS__'.$auth_source_translation_array[$auth_source_key]])) {
               $auth_item_new->setUserID($user_change_array[$user_id_key.'__CS__'.$auth_source_translation_array[$auth_source_key]]);
            }
            $auth_manager = $authentication->getAuthManager($auth_source_translation_array[$auth_source_key]);
            $auth_manager->setContextLimit($environment->getCurrentPortalID());
            $user_id_auth_new = $auth_item_new->getUserID();
            if (!empty($user_id_auth_new)) {
               $auth_manager->save($auth_item_new);
            }
         }
         unset($user_id_auth_new);

         $user_item_new = $user_item->cloneData();
         if ($user_item_new->isModerator()) {
            $user_item_new->makeUser();
         }
         $user_item_new->setContextID($environment->getCurrentPortalID());
         $temp_user = $environment->getCurrentUserItem();
         $user_item_new->setCreatorItem($temp_user);
         if (!empty($user_change_array[$user_id_key.'__CS__'.$auth_source_translation_array[$auth_source_key]])) {
            $user_item_new->setUserID($user_change_array[$user_id_key.'__CS__'.$auth_source_translation_array[$auth_source_key]]);
         }
         $user_id_user_new = $user_item_new->getUserID();

         // AUTH: auth source translation
         $user_item_new->setAuthSource($auth_source_translation_array[$auth_source_key]);
         // AUTH: auth source transaltion

         if (!empty($user_id_user_new)) {
            $user_item_new->save();
            $user_item_new->setCreatorID2ItemID();
         }
      }

      // external auth source
      foreach ($user_array_new as $key => $user_item) {
         $key_array = explode('__CS__',$key);
         $user_id_key = $key_array[0];
         $auth_source_key = $key_array[1];

         $user_item_new = $user_item->cloneData();
         $user_item_new->setContextID($environment->getCurrentPortalID());
         $temp_user = $environment->getCurrentUserItem();
         $user_item_new->setCreatorItem($temp_user);
         $user_id_user_new = $user_item_new->getUserID();

         // AUTH: auth source translation
         $user_item_new->setAuthSource($auth_source_key);
         // AUTH: auth source transaltion

         if (!empty($user_id_user_new)) {
            $user_item_new->save();
            $user_item_new->setCreatorID2ItemID();
         }
      }

      // change user_ids of user in rooms to move
      // and change auth source of user in rooms to move
      $room_item = $room_list->getFirst();
      while ($room_item) {
         $user_manager = $environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($room_item->getItemID());
         $user_manager->select();
         $user_list = $user_manager->get();
         if ($user_list->isNotEmpty()) {
            $user_item = $user_list->getFirst();
            while ($user_item) {
               $user_id_test = $user_item->getUserID();
               if (!empty($user_id_test) and !empty($user_change_array[strtoupper($user_id_test).'__CS__'.$auth_source_translation_array[$user_item->getAuthSource()]])) {
                  $user_item->setUserID($user_change_array[strtoupper($user_id_test).'__CS__'.$auth_source_translation_array[$user_item->getAuthSource()]]);
               }

               // AUTH: auth source translation
               $new_auth_source_for_user = $auth_source_translation_array[$user_item->getAuthSource()];
               $user_item->setAuthSource($new_auth_source_for_user);
               // AUTH: auth source transaltion

               $user_item->setChangeModificationOnSave(false);
               $user_item->setSaveWithoutLinkModifier();
               $user_item->save();

               $user_item = $user_list->getNext();
            }
         }

         // delete old links from community room to project rooms
         // before saving at new portal
         if (!$copy_links_between_rooms and $room_item->isCommunityRoom()) {
            $room_item->setProjectListByID(array());
            $room_item->save();
         }

         // move files from old portal folder to new portal folder
         $old_context = $room_item->getContextID();
         $new_context = $environment->getCurrentPortalID();
         if ($old_context != $new_context) {
            $disc_manager = $environment->getDiscManager();
            $disc_manager->moveFiles($room_item->getItemID(),$old_context,$new_context);
         }

         $room_item->setContextID($environment->getCurrentPortalID());

         // set link between project and community room
         if ($copy_links_between_rooms and $room_item->isProjectRoom()) {
            $temp_array = array();
            $temp_array[] = $item->getItemID();
            $room_item->setCommunityListByID($temp_array);
            unset($temp_array);
         }

         // unlock room
         if ($item->getItemID() == $room_item->getItemID()) {
            $room_item->unlockForMove();
         }

         // save room with new context id
         $room_item->save();
         $room_item = $room_list->getNext();
      }

      // send email to users
      $current_portal = $environment->getCurrentPortalItem();
      foreach ($user_array_all as $user_item) {
         $language_user = $user_item->getLanguage();
         if ($language_user == 'browser') {
            $language = $environment->getSelectedLanguage();
         } else {
            $language = $language_user;
         }
         $translator->setSelectedLanguage($language);

         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_to($user_item->getEmail());

          global $symfonyContainer;
          $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
          $mail->set_from_email($emailFrom);

         $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
         $mail->set_reply_to_name($current_user->getFullname());
         $mail->set_reply_to_email($current_user->getEmail());

         // subject
         if (count($user_room_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()]) == 1) {
            $subject = $translator->getMessage('MOVE_ROOM_MAIL_SUBJECT_SUCCESS_S',$room_name_array[$user_room_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()][0]]);
         } else {
            $subject = $translator->getMessage('MOVE_ROOM_MAIL_SUBJECT_SUCCESS_PL',$old_portal->getTitle());
         }
         $mail->set_subject($subject);

         // body
         $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         if (count($user_room_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()]) == 1) {
            $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_SUCESS_S',$old_portal->getTitle(),$current_portal->getTitle()).LF;
         } else {
            $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_SUCESS_PL',$old_portal->getTitle(),$current_portal->getTitle()).LF;
         }
         foreach ($user_room_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()] as $room_id) {
            $body .= $room_name_array[$room_id].LF;
            $body .= '   http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_id.LF.LF;
         }
         if (array_key_exists(strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$user_item->getAuthSource()],$user_change_array)) {
            $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_SUCCESS_ACCOUNT_COPY_CHANGE',$user_item->getUserID(),$user_change_array[$user_item->getUserID().'__CS__'.$auth_source_translation_array[$user_item->getAuthSource()]]);
         } elseif (array_key_exists(strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource(),$user_array)) {
            $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_SUCCESS_ACCOUNT_COPY',$user_item->getUserID());
         } elseif (array_key_exists(strtoupper($user_item->getUserID()).'__CS__'.$auth_source_translation_array[$user_item->getAuthSource()],$user_array_no_change)) {
            if (count($user_room_array[strtoupper($user_item->getUserID()).'__CS__'.$user_item->getAuthSource()]) == 1) {
               $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_SUCCESS_ACCOUNT_NO_COPY_S',$user_item->getUserID());
            } else {
               $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_SUCCESS_ACCOUNT_NO_COPY_PL',$user_item->getUserID());
            }
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_TO_NEW_PORTAL').LF;
         $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_portal->getItemID();
         $mail->set_message($body);
         $mail->send();
         unset($mail);
      }

      // close task
      $task_manager = $environment->getTaskManager();
      $task_item = $task_manager->getItem($tid);
      $task_creator = $task_item->getCreatorItem();
      $task_item->setStatus('CLOSED');
      $task_item->save();

      // send email to moderator of old portal
      $portal_manager = $environment->getPortalManager();
      $portal = $portal_manager->getItem($old_portal_id);
      $language_portal = $portal->getLanguage();
      if ($language_portal == 'user') {
         $language_user = $task_creator->getLanguage();
         if ($language_user == 'browser') {
            $language = $environment->getSelectedLanguage();
         } else {
            $language = $language_user;
         }
      } else {
         $language = $language_portal;
      }
      $translator->setSelectedLanguage($language);

      include_once('classes/cs_mail.php');
      $mail = new cs_mail();
      $mail->set_to($task_creator->getEmail());
       global $symfonyContainer;
       $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
       $mail->set_from_email($emailFrom);

      $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
      $mail->set_reply_to_name($current_user->getFullname());
      $mail->set_reply_to_email($current_user->getEmail());
      $mail->set_subject('RE: '.$translator->getMessage('MOVE_ROOM_MAIL_SUBJECT',$current_portal->getTitle()));
      $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
      $body .= LF.LF;
      $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_AGREE',$item->getTitle(),$current_portal->getTitle());
      $body .= LF.LF;
      $body .= $translator->getMessage('MOVE_ROOM_MAIL_BODY_TO_ROOM',$item->getTitle()).LF;
      $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$item->getItemID();
      $mail->set_message($body);
      $mail->send();

      // move wiki
      if ( $item->existWiki() ) {
         $wiki_manager = $environment->getWikiManager();
         $wiki_manager->moveWiki($item,$old_portal_id);
      }

      $params = array();
      $params['room_id'] = $iid;
      redirect($environment->getCurrentContextID(),'home', 'index', $params);
   }

   // else trigger error
   else {
      include_once('functions/error_functions.php');trigger_error('lost task to do',E_USER_ERROR);
   }
}
?>
