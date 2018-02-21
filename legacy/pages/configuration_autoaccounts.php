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

// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$context_item->isOpen() and !$context_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator() || !$environment->inPortal()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}

// Access granted
else {
   if ( isset($_GET['selection']) ){
      $date_array = $session->getValue('date_array');
      // Find out what to do
      if ( isset($_POST['option']) ) {
         $command = $_POST['option'];
      } else {
         $command = '';
      }

      // Cancel editing
      if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
         redirect($environment->getCurrentContextID(),'configuration', 'index','');
      }
      // Show form and/or save item
      else {
         // Initialize the form
         $class_params= array();
         $class_params['environment'] = $environment;
         $form = $class_factory->getClass(CONFIGURATION_AUTOACCOUNTS_SELECTION_FORM,$class_params);
         unset($class_params);
         $form->setArray($date_array[0]);
         $form->setAuthSource($session->getValue('autoaccounts_auth_source'));
         // Load form data from postvars
         if ( !empty($_POST) ) {
            $values = $_POST;
            $form->setFormPost($values);
         }

         $form->prepareForm();
         $form->loadValues();
         if ( !empty($command) and
            isOption($command, $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_CREATE_BUTTON')) ) {
            $correct = $form->check();
            if($correct){
               $account_array = auto_create_accounts($date_array);
               //$params['show_list']= true;
               //redirect($environment->getCurrentContextID(),'configuration','autoaccounts',$params);
               $form->show_account_array($account_array);
            }
         }
         // display form
         $class_params = array();
         $class_params['environment'] = $environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$class_params);
         unset($class_params);
         $params['selection']= true;
         $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','autoaccounts',$params));
         $form_view->setForm($form);
         $page->addForm($form_view);

      }
   } elseif( isset($_GET['show_list']) ){
      //pr('Ergebniss');
   }else{
      // function for page edit
      // - to check files for virus
      if (isset($c_virus_scan) and $c_virus_scan) {
         include_once('functions/page_edit_functions.php');
      }


      // Find out what to do
      if ( isset($_POST['option']) ) {
         $command = $_POST['option'];
      } else {
         $command = '';
      }
      // Cancel editing
      if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
         redirect($environment->getCurrentContextID(),'configuration','index',array());
      }

      // Show form and/or save item
      else {
         // Initialize the form
         $class_params= array();
         $class_params['environment'] = $environment;
         $form = $class_factory->getClass(CONFIGURATION_AUTOACCOUNTS_FORM,$class_params);
         unset($class_params);
         // Load form data from postvars
         if ( !empty($_POST) ) {
            if ( !empty($_FILES) ) {
               if ( !empty($_FILES['dates_upload']['tmp_name']) ) {
                  $new_temp_name = $_FILES['dates_upload']['tmp_name'].'_TEMP_'.$_FILES['dates_upload']['name'];
                  move_uploaded_file($_FILES['dates_upload']['tmp_name'],$new_temp_name);
                  $_FILES['dates_upload']['tmp_name'] = $new_temp_name;
                  $session_item = $environment->getSessionItem();
                  if ( isset($session_item) ) {
                     $current_iid = $environment->getCurrentContextID();
                     $session_item->setValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_temp_name',$new_temp_name);
                     $session_item->setValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_name',$_FILES['dates_upload']['name']);
                  }
               }
               $values = array_merge($_POST,$_FILES);
            } else {
               $values = $_POST;
            }
            $form->setFormPost($values);
         }

         $form->prepareForm();
         $form->loadValues();

         // Save item
         if ( !empty($command)
              and isOption($command, $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_UPLOAD_FILE_BUTTON'))
            ) {

            $correct = $form->check();

            if ( $correct
                 and empty($_FILES['dates_upload']['tmp_name'])
                 and !empty($_POST['hidden_dates_upload_name'])
               ) {
               $session_item = $environment->getSessionItem();
               if ( isset($session_item) ) {
                  $current_iid = $environment->getCurrentContextID();
                  $_FILES['dates_upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_temp_name');
                  $_FILES['dates_upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_name');
                  $session_item->unsetValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_temp_name');
                  $session_item->unsetValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_name');
               }
            }

            if ( $correct
               and ( !isset($c_virus_scan)
               or !$c_virus_scan
               or page_edit_virusscan_isClean($_FILES['dates_upload']['tmp_name'],$_FILES['dates_upload']['name']))) {
               $data_array = file($_FILES['dates_upload']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
               $dates_data_array = array();
               if($_POST['autoaccounts_seperator'] == $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT')){
                  $found_comma = false;
                  $found_semicolon = false;
                  for ($i = 0; $i < count($data_array); $i++){
                     $temp_string = $data_array[$i];
                     if(stristr($temp_string, ',')){
                        $found_comma = true;
                     }
                     if(stristr($temp_string, ';')){
                        $found_semicolon = true;
                     }
                  }
                  if($found_comma and !$found_semicolon){
                     $seperator = ',';
                  } else if (!$found_comma and $found_semicolon){
                     $seperator = ';';
                  }
                  else {
                     $params = array();
                     $params['seperator_not_found'] = true;
                     redirect($environment->getCurrentContextID(),'configuration','autoaccounts',$params);
                  }
               } else {
                  $seperator = $_POST['autoaccounts_seperator'];
               }
               for ($i = 0; $i < count($data_array); $i++){
                  if ($i == 0){
                     $temp_data = str_replace('"','',$data_array[$i]);
                     $data_header_array = explode($seperator,$temp_data);
                  }else{
                     $temp_data = str_replace('"','',$data_array[$i]);
                     $temp_data_array = explode($seperator,$temp_data);
                     for ($j = 0; $j < count($data_header_array); $j++){
                        if ( isset($temp_data_array[$j]) ){
                           include_once('functions/text_functions.php');
                           $dates_data_array[$i-1][trim($data_header_array[$j])] = cs_utf8_encode($temp_data_array[$j]);
                        }
                     }
                  }
               }
               $session->setvalue('date_array', $dates_data_array);
               $session->setValue('autoaccounts_auth_source', $_POST['autoaccounts_auth_source']);
               $params['selection']= true;
               redirect($environment->getCurrentContextID(),'configuration','autoaccounts',$params);
            }
         }

         // display form
         $class_params = array();
         $class_params['environment'] = $environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$class_params);
         unset($class_params);
         //$form_view->setAction(curl($environment->getCurrentContextID(),CS_DATE_TYPE,'import',''));
         $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','autoaccounts',''));
         $form_view->setForm($form);
         $page->addForm($form_view);
      }
   }
}

function auto_create_accounts($date_array){
   global $environment;
   $translator = $environment->getTranslationObject();

   $account_auth_source = $_POST['autoaccounts_auth_source'];
   $account_array = array();
   $allow_add_account = false;
   $auth_source_manager = $environment->getAuthSourceManager();
   $auth_source_item = $auth_source_manager->getItem($account_auth_source);
   if($auth_source_item->allowAddAccount()){
      $allow_add_account = true;
   }
   foreach($date_array as $account){
      if($allow_add_account){
         $temp_account_lastname = $account[$_POST['autoaccounts_lastname']];
         $temp_account_firstname = $account[$_POST['autoaccounts_firstname']];
         $temp_account_email = $account[$_POST['autoaccounts_email']];
         $lastname_length = strlen($temp_account_lastname);
         $firstname_length = strlen($temp_account_firstname);
         $email_length = strlen($temp_account_email);
         $account_info_missing = false;
         if(($lastname_length == 0) or ($firstname_length == 0) or ($email_length == 0)){
            $account_info_missing = true;
         }
         $temp_account_account = $account[$_POST['autoaccounts_account']];
         $account_length = strlen($temp_account_account);
         if($account_length == 0){
            $temp_account_account = strtolower($temp_account_lastname);
            $account_generated = true;
         }
         $temp_account_account = get_free_account($temp_account_account, $account_auth_source);
         $temp_account_password = $account[$_POST['autoaccounts_password']];
         $password_length = strlen($temp_account_password);
         if($password_length == 0){
            $temp_account_password = generate_password();
            $password_generated = true;
         } else {
            $password_generated = false;
         }
         $temp_account_rooms = $account[$_POST['autoaccounts_rooms']];
         $temp_account_rooms = trim($temp_account_rooms);
         if(stristr($temp_account_rooms, ' ')){
            $temp_account_rooms_array = explode(' ', $temp_account_rooms);
         } else if(stristr($temp_account_rooms, ';')){
            $temp_account_rooms_array = explode(';', $temp_account_rooms);
         } else if(stristr($temp_account_rooms, ',')){
            $temp_account_rooms_array = explode(',', $temp_account_rooms);
         }
         $room_length = strlen($temp_account_rooms);
         if($room_length != 0 and empty($temp_account_rooms_array)){
            $temp_account_rooms_array = array($temp_account_rooms);
         }
         if(!$account_info_missing){
            $found_user_by_email = false;
            $most_recent_account = null;

            if((!empty($_POST['autoaccount_no_new_account_when_email_exists'])) and ($_POST['autoaccount_no_new_account_when_email_exists'] == 1)){
               //Test auf E-Mail-Adresse...
               $user_manager = $environment->getUserManager();
               $user_manager->resetCacheSQL();
               $user_manager->resetLimits();
               $user_manager->setContextLimit($environment->getCurrentContextID());
               $user_manager->setEmailLimit($temp_account_email);
               $user_manager->select();
               $user_list = $user_manager->get();
               if (!$user_list->isEmpty()) {
                  if ($user_list->getCount() > 0) {
                     $found_user_by_email = true;
                     $temp_acount = $user_list->getFirst();
                     while($temp_acount){
                        if($most_recent_account == null){
                           $most_recent_account = $temp_acount;
                        } else {
                           if($temp_acount->getLastLogin > $most_recent_account->getLastLogin){
                              $most_recent_account = $temp_acount;
                           }
                        }
                        $temp_acount = $user_list->getNext();
                     }
                  }
               }
            }

            if(!$found_user_by_email){
               $authentication = $environment->getAuthenticationObject();
               $current_portal = $environment->getCurrentPortalItem();
               $current_portal_id = $environment->getCurrentPortalID();
               $auth_source_id = $account_auth_source;

               $new_account = $authentication->getNewItem();
               $new_account->setUserID($temp_account_account);
               $new_account->setPassword($temp_account_password);
               $new_account->setFirstname($temp_account_firstname);
               $new_account->setLastname($temp_account_lastname);
               $new_account->setEmail($temp_account_email);
               $new_account->setPortalID($current_portal_id);
               $new_account->setAuthSourceID($auth_source_id);
               $save_only_user = false;
               $authentication->save($new_account,$save_only_user);
               $temp_user = $authentication->getUserItem();
               $temp_user->makeUser();
               $temp_user->save();

               $temp_account_array = array();
               $temp_account_array['lastname'] = $temp_account_lastname;
               $temp_account_array['firstname'] = $temp_account_firstname;
               $temp_account_array['email'] = $temp_account_email;
               $temp_account_array['account'] = $temp_account_account;
               if($temp_account_account != $account[$_POST['autoaccounts_account']]){
                  if(!$account_generated){
                     $temp_account_array['account_csv'] = $account[$_POST['autoaccounts_account']];
                     $temp_account_array['account_changed'] = 'changed';
                  } else {
                     $temp_account_array['account_csv'] = strtolower($temp_account_lastname);
                     $temp_account_array['account_changed'] = 'generated';
                  }
               } else {
                  $temp_account_array['account_changed'] = false;
               }
               $temp_account_array['password'] = $temp_account_password;
               if($password_generated){
                  $temp_account_array['password_generated'] = true;
               } else {
                  $temp_account_array['password_generated'] = false;
               }
               $temp_account_array['found_account_by_email'] = false;
               $rooms_added_to = add_user_to_rooms($temp_user, $temp_account_rooms_array, $password_generated, $temp_account_password);
               $temp_account_array['rooms_added'] = $rooms_added_to;
               $temp_account_array['rooms'] = $temp_account_rooms_array;
            } else {
               $temp_account_array = array();
               $temp_account_array['lastname'] = $most_recent_account->getFirstname();
               $temp_account_array['firstname'] = $most_recent_account->getLastname();
               $temp_account_array['email'] = $most_recent_account->getEmail();
               $temp_account_array['account'] = $most_recent_account->getUserID();
               $temp_account_array['account_changed'] = false;
               $temp_account_array['password'] = '';
               $temp_account_array['password_generated'] = false;
               $temp_account_array['found_account_by_email'] = true;
               $rooms_added_to = add_user_to_rooms($most_recent_account, $temp_account_rooms_array);
               $temp_account_array['rooms_added'] = $rooms_added_to;
               $temp_account_array['rooms'] = $temp_account_rooms_array;
            }
            $account_array[] = $temp_account_array;
         } else {
            $temp_account_array = array();
            if($lastname_length == 0){
               $temp_account_array['lastname'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_INFO_MISSING');
            } else {
               $temp_account_array['lastname'] = $temp_account_lastname;
            }
            if($firstname_length == 0){
               $temp_account_array['firstname'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_INFO_MISSING');
            } else {
               $temp_account_array['firstname'] = $temp_account_firstname;
            }
            if($email_length == 0){
               $temp_account_array['email'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_INFO_MISSING');
            } else {
               $temp_account_array['email'] = $temp_account_email;
            }
            $temp_account_array['account'] = $account[$_POST['autoaccounts_account']];
            $temp_account_array['account_changed'] = false;
            $temp_account_array['password'] = '';
            $temp_account_array['password_generated'] = false;
            $temp_account_array['found_account_by_email'] = false;
            $temp_account_array['account_not_created'] = true;
            $temp_account_array['rooms'] = array();
            $account_array[] = $temp_account_array;
         }
      }

      // don not allow add accounts
      else {
         $temp_account_account = $account[$_POST['autoaccounts_account']];
         $account_length = strlen($temp_account_account);
         if($account_length == 0){
            $temp_account_array = array();
            $temp_account_array['lastname'] = $account[$_POST['autoaccounts_lastname']];
            $temp_account_array['firstname'] = $account[$_POST['autoaccounts_firstname']];
            $temp_account_array['email'] = $account[$_POST['autoaccounts_email']];
            $temp_account_array['account'] = $account[$_POST['autoaccounts_account']];
            $temp_account_array['account_changed'] = false;
            $temp_account_array['password'] = '';
            $temp_account_array['password_generated'] = false;
            $temp_account_array['found_account_by_email'] = false;
            $temp_account_array['account_not_created'] = true;
            $temp_account_array['rooms'] = array();
            $temp_account_array['has_comment'] = true;
            $temp_account_array['comment'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_NO_USER_ID');
            $account_array[] = $temp_account_array;
         } else {
            $user_manager = $environment->getUserManager();
            $user_item = $user_manager->getItemByUserIDAuthSourceID($account[$_POST['autoaccounts_account']],$auth_source_item->getItemID());
            $account_generated = false;
            if ( !isset($user_item) ) {
               $auth_connection = $auth_source_item->getAuthConnection();
               $new_account_data = $auth_connection->get_data_for_new_account($account[$_POST['autoaccounts_account']], $account[$_POST['autoaccounts_password']]);
               if ( !empty($new_account_data)
                    and !empty($new_account_data['firstname'])
                    and !empty($new_account_data['lastname'])
                  ) {
                  $user_manager = $environment->getUserManager();
                  $user_item = $user_manager->getNewItem();
                  $user_item->setUserID($account[$_POST['autoaccounts_account']]);
                  if ( !empty($account[$_POST['autoaccounts_firstname']]) ) {
                     $user_item->setFirstname($account[$_POST['autoaccounts_firstname']]);
                  } else {
                     $user_item->setFirstname($new_account_data['firstname']);
                  }
                  if ( !empty($account[$_POST['autoaccounts_lastname']]) ) {
                     $user_item->setLastname($account[$_POST['autoaccounts_lastname']]);
                  } else {
                     $user_item->setLastname($new_account_data['lastname']);
                  }
                  if ( !empty($account[$_POST['autoaccounts_email']]) ) {
                     $user_item->setEmail($account[$_POST['autoaccounts_email']]);
                  } elseif ( !empty($new_account_data['email']) ) {
                     $user_item->setEmail($new_account_data['email']);
                  } else {
                      global $symfonyContainer;
                      $email = $symfonyContainer->getParameter('commsy.email.from');

                     $user_item->setEmail($email);
                     $user_item->setHasToChangeEmail();
                  }
                  $user_item->setAuthSource($account_auth_source);
                  $user_item->makeUser();
                  $user_item->save();
                  $account_generated = true;
               }
            }
            if ( !empty($user_item) ) {
               $temp_account_rooms = $account[$_POST['autoaccounts_rooms']];
               $temp_account_rooms = trim($temp_account_rooms);
               if(stristr($temp_account_rooms, ' ')){
                  $temp_account_rooms_array = explode(' ', $temp_account_rooms);
               } else if(stristr($temp_account_rooms, ';')){
                  $temp_account_rooms_array = explode(';', $temp_account_rooms);
               } else if(stristr($temp_account_rooms, ',')){
                  $temp_account_rooms_array = explode(',', $temp_account_rooms);
               }
               $room_length = strlen($temp_account_rooms);
               if($room_length != 0 and empty($temp_account_rooms_array)){
                  $temp_account_rooms_array = array($temp_account_rooms);
               }

               $temp_account_array = array();
               $temp_account_array['lastname'] = $user_item->getFirstname();
               $temp_account_array['firstname'] = $user_item->getLastname();
               $temp_account_array['email'] = $user_item->getEmail();
               $temp_account_array['account'] = $user_item->getUserID();
               $temp_account_array['account_changed'] = false;
               $temp_account_array['password'] = '';
               $temp_account_array['password_generated'] = false;
               $temp_account_array['found_account_by_email'] = false;
               $rooms_added_to = add_user_to_rooms($user_item, $temp_account_rooms_array);
               $temp_account_array['rooms_added'] = $rooms_added_to;
               $temp_account_array['rooms'] = $temp_account_rooms_array;
               $temp_account_array['account_not_created'] = !$account_generated;
               if (!$account_generated) {
                  $temp_account_array['has_comment'] = true;
                  $temp_account_array['comment'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_FOUND_ACCOUNT');
               }
               $account_array[] = $temp_account_array;
            } else {
               $temp_account_array = array();
               $temp_account_array['lastname'] = $account[$_POST['autoaccounts_lastname']];
               $temp_account_array['firstname'] = $account[$_POST['autoaccounts_lastname']];
               $temp_account_array['email'] = $account[$_POST['autoaccounts_lastname']];
               $temp_account_array['account'] = $account[$_POST['autoaccounts_account']];
               $temp_account_array['account_changed'] = false;
               $temp_account_array['password'] = '';
               $temp_account_array['password_generated'] = false;
               $temp_account_array['found_account_by_email'] = false;
               $temp_account_array['account_not_created'] = true;
               $temp_account_array['rooms'] = array();
               $temp_account_array['has_comment'] = true;
               $temp_account_array['comment'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_DID_NOT_GET_DATA');
               $account_array[] = $temp_account_array;
            }
         }
      }
   }
   return $account_array;
}

function get_free_account($temp_account_account, $account_auth_source, $index = 0){
   global $environment;
   $authentication = $environment->getAuthenticationObject();
   $current_portal = $environment->getCurrentPortalItem();
   $current_portal_id = $environment->getCurrentPortalID();
   //$auth_source_id = $current_portal->getAuthDefault();
   $auth_source_id = $account_auth_source;
   if($index > 0){
      if ( $authentication->is_free($temp_account_account . $index, $auth_source_id) ) {
         return $temp_account_account . $index;
      } else {
         $index++;
         return get_free_account($temp_account_account, $account_auth_source, $index);
      }
   } else {
      if ( $authentication->is_free($temp_account_account, $auth_source_id) ) {
         return $temp_account_account;
      } else {
         $index++;
         return get_free_account($temp_account_account, $account_auth_source, $index);
      }
   }
}

function generate_password(){
   $length = 3;
   $password = '';
   for ($i=0;$i <= $length;$i++){
      $password .= chr(rand(97,122));
      $password .= chr(rand(49,57));
      $password .= chr(rand(65,90));
   }
   return $password;
}

function add_user_to_rooms($user, $room_array, $password_generated = false, $temp_account_password = ''){
   global $environment;

   $rooms_added_to = array();
   $rooms_added_to['added'] = array();
   $rooms_added_to['not_existing'] = array();
   $room_manager = $environment->getRoomManager();
   $private_room_user_item = $user->getRelatedPrivateRoomUserItem();
   foreach($room_array as $room){
      // gibt es den Raum überhaupt?
      $room_item = $room_manager->getItem($room);
      if(isset($room_item)){
         // gibt es den Benutzer in dem Raum schon?
         $related_user_item = $user->getRelatedUserItemInContext($room);
         if(!isset($related_user_item)){
            if ( isset($private_room_user_item) ) {
               $user_item = $private_room_user_item->cloneData();
            } else {
               $user_item = $user->cloneData();
            }
            $user_item->setContextID($room);
            // Wie ist der Zugangsstatus zum Raum?
            if($room_item->checkNewMembersNever()){
               $user_item->setStatus(2);
               if(($_POST['autoaccount_send_email'] == 'autoaccount_send_email_commsy') or $password_generated){
                  write_email_to_user($user_item, $room, $password_generated, $temp_account_password);
               }
            } else {
               $user_item->setStatus(1);
            }
            $user_item->save();

            // task
            if ( !$user_item->isUser() ) {
               $current_user = $environment->getCurrentUserItem();
               $task_manager = $environment->getTaskManager();
               $task_item = $task_manager->getNewItem();
               $task_item->setCreatorItem($current_user);
               $task_item->setContextID($room_item->getItemID());
               $task_item->setTitle('TASK_USER_REQUEST');
               $task_item->setStatus('REQUEST');
               $task_item->setItem($user_item);
               $task_item->save();
               unset($current_user);
               unset($task_item);
               unset($task_manager);
            }

            $rooms_added_to['added'][] = $room;
            write_email_to_moderators($user_item, $room);
         }
      } else {
         $rooms_added_to['not_existing'][] = $room;
      }
   }
   if($_POST['autoaccount_send_email'] == 'autoaccount_send_email_form'){
      include_once('classes/cs_mail.php');
      $mail = new cs_mail();
      $mail->set_to($user->getEmail());

       global $symfonyContainer;
       $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
       $mail->set_from_email($emailFrom);

      $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
      $mail->set_subject($_POST['autoaccount_email_subject']);
      $mail->set_message($_POST['autoaccount_email_text']);
      $mail->send();
   }
   return $rooms_added_to;
}

function write_email_to_moderators($user_item, $room){
   global $environment;
   $translator = $environment->getTranslationObject();

   $room_manager = $environment->getRoomManager();
   $room_item = $room_manager->getItem($room);

   $user_manager = $environment->getUserManager();
   $user_manager->resetLimits();
   $user_manager->setModeratorLimit();
   $user_manager->setContextLimit($room);
   $user_manager->select();
   $user_list = $user_manager->get();
   $email_addresses = array();
   $moderator_item = $user_list->getFirst();
   $recipients = '';
   $language = $room_item->getLanguage();
   while ($moderator_item) {
      $want_mail = $moderator_item->getAccountWantMail();
      if (!empty($want_mail) and $want_mail == 'yes') {
         if ($language == 'user' and $moderator_item->getLanguage() == 'browser') {
            $email_addresses[$environment->getSelectedLanguage()][] = $moderator_item->getEmail();
         } elseif ($language == 'user' and $moderator_item->getLanguage() != 'browser') {
            $email_addresses[$moderator_item->getLanguage()][] = $moderator_item->getEmail();
         } else {
            $email_addresses[$room_item->getLanguage()][] = $moderator_item->getEmail();
         }
         $recipients .= $moderator_item->getFullname().LF;
      }
       $moderator_item = $user_list->getNext();
   }
   if ( !$room_item->checkNewMembersNever() and !$room_item->checkNewMembersWithCode()) {
      $check_message = 'YES'; // for mail body
   } else {
      $check_message = 'NO';
   }
   foreach ($email_addresses as $language => $email_array) {
      if (count($email_array) > 0) {
         $old_lang = $translator->getSelectedLanguage();
         $translator->setSelectedLanguage($language);
         $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
         $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         if ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('USER_JOIN_COMMUNITY_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
         } else {
            $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
         }
         $body .= LF.LF;
         if ($check_message == 'YES') {
            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
         } else {
            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
         $body .= LF;
         if ($check_message == 'YES') {
            $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
            $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room.'&mod=account&fct=index'.'&selstatus=1';
         } else {
            $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room;
         }
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_to(implode(',',$email_array));

          global $symfonyContainer;
          $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
          $mail->set_from_email($emailFrom);

         $current_context = $environment->getCurrentContextItem();
         $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
         $mail->set_from_name($room_item->getTitle());
         $mail->set_reply_to_name($user_item->getFullname());
         $mail->set_reply_to_email($user_item->getEmail());
         $mail->set_subject($subject);
         $mail->set_message($body);
         $mail->send();
         $translator->setSelectedLanguage($old_lang);
      }
   }
}

function write_email_to_user($user_item, $room, $password_generated = false, $temp_account_password = ''){
   global $environment;
   $room_manager = $environment->getRoomManager();
   $room_item = $room_manager->getItem($room);

   // get contact moderator (TBD) now first moderator
   $user_list = $room_item->getModeratorList();
   $contact_moderator = $user_list->getFirst();

   // change context
   $translator = $environment->getTranslationObject();
   $translator->setEmailTextArray($room_item->getEmailTextArray());
   if ($room_item->isProjectRoom()) {
      $translator->setContext('project');
   } else {
      $translator->setContext('community');
   }
   $save_language = $translator->getSelectedLanguage();
   $translator->setSelectedLanguage($room_item->getLanguage());

   // Datenschutz
   if($environment->getCurrentPortalItem()->getHideAccountname()){
   	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
   } else {
   	$userid = $user->getUserID();
   }
   
   // email texts
   $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
   $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
   $body .= LF.LF;
   $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
   $body .= LF.LF;
   $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room_item->getTitle());
   $body .= LF.LF;
   if($password_generated){
      $body .= $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_PASSWORD_GENERATED',$temp_account_password);
      $body .= LF.LF;
   }
   $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
   $body .= LF.LF;
   $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();

   // send mail to user
   include_once('classes/cs_mail.php');
   $mail = new cs_mail();
   $mail->set_to($user_item->getEmail());
   $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));

    global $symfonyContainer;
    $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
    $mail->set_from_email($emailFrom);

   $mail->set_reply_to_email($contact_moderator->getEmail());
   $mail->set_reply_to_name($contact_moderator->getFullname());
   $mail->set_subject($subject);
   $mail->set_message($body);
   $mail->send();
}
?>