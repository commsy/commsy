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

// Get the translator object
$translator = $environment->getTranslationObject();

if (isset($_GET['return_attach_item_list'])){
   $_POST = $session->getValue('linked_items_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   if ( !empty($_FILES['upload']['tmp_name']) ) {
      $new_temp_name = $_FILES['upload']['tmp_name'].'_TEMP_'.$_FILES['upload']['name'];
      move_uploaded_file($_FILES['upload']['tmp_name'],$new_temp_name);
      $_FILES['upload']['tmp_name'] = $new_temp_name;
      $_POST = array_merge($_POST,$_FILES);
      exit;
   }
   $session->setValue($current_iid.'_post_vars', $_POST);

   if ( isset($_POST[$rubric_type]) ) {
      $session->setValue($current_iid.$infix.'_attach_ids', $_POST[$rubric_type]);
   } else {
      $session->setValue($current_iid.$infix.'_attach_ids', array());
   }
   $session->setValue($current_iid.$infix.'_back_module', 'user');
   $params = array();
   $params['ref_iid'] = $current_iid;
   $params['mode'] = 'formattach';
   redirect($environment->getCurrentContextID(), type2Module($rubric_type), 'index', $params);
}

function attach_return ($rubric_type, $current_iid) {
   global $session;
   $infix = '_'.$rubric_type;
   $attach_ids = $session->getValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.$infix.'_back_module');
   return $attach_ids;
}

// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session;
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_group_attach_ids');
#   $session->unsetValue($current_iid.'_topic_attach_ids');
   $session->unsetValue($current_iid.'_group_back_module');
#   $session->unsetValue($current_iid.'_topic_back_module');
}

// function for page edit
// - to check files for virus
if (isset($c_virus_scan) and $c_virus_scan) {
   include_once('functions/page_edit_functions.php');
}

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

// Coming back from attaching items
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}
if (!empty($_GET['iid'])) {
   $iid = $_GET['iid'];
} elseif (!empty($_POST['iid'])) {
   $iid = $_POST['iid'];
} else {
   if ($session->issetValue('linked_items_post_vars')){
      $session_values = $session->getValue('linked_items_post_vars');
      if (isset($session_values['iid'])){
         $iid = $session_values['iid'];
      }
   }else{
      include_once('functions/error_functions.php');
      trigger_error('No user selected!',E_USER_ERROR);
   }
}

$user_manager = $environment->getUserManager();
$user_item = $user_manager->getItem($iid);
$room_item = $environment->getCurrentContextItem();

// Check access rights
if (!empty($iid) and $iid != 'NEW') {
   $current_user = $environment->getCurrentUserItem();
   if(empty($_POST)){
      $link_item_array = array();
      if (!$environment->inCommunityRoom()){
          $link_item_array = $user_item->getLinkedItemIDArray(CS_GROUP_TYPE);
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$link_item_array);
   }
   if (!$user_item->mayEdit($current_user)) { // only user should be allowed to edit her/his own account
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $error_string = $translator->getMessage('LOGIN_NOT_ALLOWED');
      $errorbox->setText($error_string);
      $page->add($errorbox);
      $command = 'error';
   }
}
$context_item = $environment->getCurrentContextItem();
if (!$context_item->isOpen()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = $translator->getMessage('PROJECT_ROOM_IS_CLOSED',$context_item->getTitle());
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') { // only if user is allowed to edit user
   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(USER_FORM,$class_params);
   unset($class_params);
   // cancel edit process
   if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      $session->unsetValue('linked_items_post_vars');
      if ( empty($_POST['iid']) ) {
         redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), 'index', '');
      } else {
         $params = array();
         $params['iid'] = $_POST['iid'];
         redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), 'detail', $params);
      }
   }

   // save user
   else {
      // Define rubric connections
      if ($environment->inCommunityRoom()){
         $rubric_connection = array();
      } else {
         $rubric_connection = array();
         $current_rubrics = $room_item->getAvailableRubrics();
         foreach ( $current_rubrics as $rubric ) {
            switch ( $rubric ) {
               case CS_GROUP_TYPE:
                  $rubric_connection[] = CS_GROUP_TYPE;
                  break;
            }
         }
      }
      $form->setRubricConnections($rubric_connection);

      // Redirect to attach groups
      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_GROUP_BUTTON')) ) {
         attach_redirect(CS_GROUP_TYPE, $iid);
      }
      include_once('include/inc_right_boxes_handling.php');

#      // Redirect to attach topics
#      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_TOPIC_BUTTON')) ) {
#         attach_redirect(CS_TOPIC_TYPE, $iid);
#      }

      // init data display
      if (!empty($_POST)) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
         if ( !empty($_FILES) ) {
         if ( !empty($_FILES['upload']['tmp_name']) ) {
            $new_temp_name = $_FILES['upload']['tmp_name'].'_TEMP_'.$_FILES['upload']['name'];
            move_uploaded_file($_FILES['upload']['tmp_name'],$new_temp_name);
            $_FILES['upload']['tmp_name'] = $new_temp_name;

            $session_item = $environment->getSessionItem();
            if ( isset($session_item) ) {
               $current_iid = $environment->getCurrentContextID();
               $session_item->setValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name',$new_temp_name);
               $session_item->setValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name',$_FILES['upload']['name']);
            }

            //resizing the userimage to a maximum width of 150px
            // + keeping a set ratio
            $srcfile = $_FILES['upload']['tmp_name'];
            $target = $_FILES['upload']['tmp_name'];
            $size = getimagesize($srcfile);
            $x_orig= $size[0];
            $y_orig= $size[1];
            //$verhaeltnis = $x_orig/$y_orig;
            $verhaeltnis = $y_orig/$x_orig;
            $max_width = 150;
            //$ratio = 1.618; // Goldener Schnitt
            //$ratio = 1.5; // 2:3
            $ratio = 1.334; // 3:4
            //$ratio = 1; // 1:1
            if($verhaeltnis < $ratio){
               // Breiter als 1:$ratio
               $source_width = ($size[1] * $max_width) / ($max_width * $ratio);
               $source_height = $size[1];
               $source_x = ($size[0] - $source_width) / 2;
               $source_y = 0;
            } else {
               // Höher als 1:$ratio
               $source_width = $size[0];
               $source_height = ($size[0] * ($max_width * $ratio)) / ($max_width);
               $source_x = 0;
               $source_y = ($size[1] - $source_height) / 2;
            }
            switch ($size[2]) {
                  case '1':
                     $im = imagecreatefromgif($srcfile);
                     break;
                  case '2':
                     $im = imagecreatefromjpeg($srcfile);
                     break;
                  case '3':
                     $im = imagecreatefrompng($srcfile);
                     break;
               }
               //$newimg = imagecreatetruecolor($show_width,$show_height);
               //imagecopyresampled($newimg, $im, 0, 0, 0, 0, $show_width, $show_height, $size[0], $size[1]);
               $newimg = imagecreatetruecolor($max_width,($max_width * $ratio));
               imagecopyresampled($newimg, $im, 0, 0, $source_x, $source_y, $max_width, ceil($max_width * $ratio), $source_width, $source_height);
               imagepng($newimg,$target);
               imagedestroy($im);
               imagedestroy($newimg);
            }
            $values = array_merge($session_post_vars,$_FILES);
         } else {
            $values = $session_post_vars;
         }
         $form->setFormPost($values);
         if (!empty($session_post_vars['is_moderator'])) {
            $form->setIsModerator(true);
         } else {
            $form->setIsModerator(false);
         }
         if (!empty($session_post_vars['with_picture'])) {
            $form->setWithPicture(true);
         } else {
            $form->setWithPicture(false);
         }
      }

      // Back from attaching groups
      elseif ( $backfrom == CS_GROUP_TYPE ) {
         $session_post_vars = $session->getValue($iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_GROUP_TYPE, $iid);
         $with_anchor = true;
         $session_post_vars[CS_GROUP_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

#      // Back from attaching topics
#      elseif ( $backfrom == CS_TOPIC_TYPE ) {
#         $session_post_vars = $session->getValue($iid.'_post_vars'); // Must be called before attach_return(...)
#         $attach_ids = attach_return(CS_TOPIC_TYPE, $iid);
#		 $with_anchor = true;
#         $session_post_vars[CS_TOPIC_TYPE] = $attach_ids;
#         $form->setFormPost($session_post_vars);
#      }

     // first call
     elseif (!empty($iid) and $iid != 'NEW') { // change existing user
         $user_manager = $environment->getUserManager();
         $user_item = $user_manager->getItem($iid);
         $form->setItem($user_item);
         $form->setIsModerator($current_user->isModerator());
         $picture = $user_item->getPicture();
         if (!empty($picture)) {
            $form->setWithPicture(true);
         }
      }
      $form->prepareForm();
      $form->loadValues();

      if ( !empty($command) AND isOption($command,$translator->getMessage('USER_CHANGE_BUTTON')) ) {
         $correct = $form->check();
         if ( $correct
              and empty($_FILES['upload']['tmp_name'])
              and !empty($_POST['hidden_upload_name'])
            ) {
            $session_item = $environment->getSessionItem();
            if ( isset($session_item) ) {
               $_FILES['upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name');
               $_FILES['upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_temp_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_user_'.$iid.'_upload_name');
            }
         }
         if ( $correct
              and ( !isset($c_virus_scan)
                    or !$c_virus_scan
                    or empty($_FILES['upload']['tmp_name'])
                    or empty($_FILES['upload']['name'])
                    or page_edit_virusscan_isClean($_FILES['upload']['tmp_name'],$_FILES['upload']['name'])
                  )
            ) {
            $user_manager = $environment->getUserManager();
            if (!empty($_POST['iid'])) { // change user
               $user_item = $user_manager->getItem($_POST['iid']);
               if ( $environment->inPrivateRoom() ) {
                  $portal_user_item = $user_item->getRelatedCommSyUserItem();
               }
            }
            $old_firstname = $user_item->getFirstName();
            $old_lastname  = $user_item->getLastName();
            if (isset($_POST['firstname'])) {
               $user_item->setFirstName($_POST['firstname']);
            }
            if (isset($_POST['lastname'])) {
               $user_item->setLastname($_POST['lastname']);
            }
            if (isset($_POST['title'])) {
               $user_item->setTitle($_POST['title']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setTitle($_POST['title']);
               }
            }
            if (isset($_POST['telephone'])) {
               $user_item->setTelephone($_POST['telephone']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setTelephone($_POST['telephone']);
               }
            }
            if (isset($_POST['birthday'])) {
               $user_item->setBirthday($_POST['birthday']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setBirthday($_POST['birthday']);
               }
            }
            if (isset($_POST['cellularphone'])) {
               $user_item->setCellularphone($_POST['cellularphone']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setCellularphone($_POST['cellularphone']);
               }
            }
            if (isset($_POST['homepage'])) {
               $user_item->setHomepage($_POST['homepage']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setHomepage($_POST['homepage']);
               }
            }
            if (isset($_POST['organisation'])) {
               $user_item->setOrganisation($_POST['organisation']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setOrganisation($_POST['organisation']);
               }
            }
            if (isset($_POST['position'])) {
               $user_item->setPosition($_POST['position']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setPosition($_POST['position']);
               }
            }
            if (isset($_POST['icq'])) {
               $user_item->setICQ($_POST['icq']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setICQ($_POST['icq']);
               }
            }
            if (isset($_POST['skype'])) {
               $user_item->setSkype($_POST['skype']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setSkype($_POST['skype']);
               }
            }
            if (isset($_POST['yahoo'])) {
               $user_item->setYahoo($_POST['yahoo']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setYahoo($_POST['yahoo']);
               }
            }
            if (isset($_POST['msn'])) {
               $user_item->setMSN($_POST['msn']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setMSN($_POST['msn']);
               }
            }
            if (isset($_POST['jabber'])) {
               $user_item->setJabber($_POST['jabber']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setJabber($_POST['jabber']);
               }
            }
            if (isset($_POST['email'])) {
               $user_item->setEmail($_POST['email']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setEmail($_POST['email']);
               }
            }
            if (isset($_POST['street'])) {
               $user_item->setStreet($_POST['street']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setStreet($_POST['street']);
               }
            }
            if (isset($_POST['zipcode'])) {
               $user_item->setZipcode($_POST['zipcode']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setZipcode($_POST['zipcode']);
               }
            }
            if (isset($_POST['city'])) {
               $user_item->setCity($_POST['city']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setCity($_POST['city']);
               }
            }
            if (isset($_POST['room'])) {
               $user_item->setRoom($_POST['room']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setRoom($_POST['room']);
               }
            }
            if (isset($_POST['description'])) {
               $user_item->setDescription($_POST['description']);
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setDescription($_POST['description']);
               }
            }

            if (
                  ( isset($_POST['deletePicture'])
                    or ( !empty($_FILES['upload']['name'])
                         and !empty($_FILES['upload']['tmp_name'])
                       )
                  )
                  and $user_item->getPicture()
               ) {
               $disc_manager = $environment->getDiscManager();
               if ( $disc_manager->existsFile($user_item->getPicture()) ) {
                  $disc_manager->unlinkFile($user_item->getPicture());
               }
               $user_item->setPicture('');
               if ( isset($portal_user_item) ) {
                  $portal_user_item->setPicture('');
               }
            }
            if ( !empty($_FILES['upload']['name']) and !empty($_FILES['upload']['tmp_name']) ) {
               $filename = 'cid'.$environment->getCurrentContextID().'_'.$user_item->getUserID().'_'.$_FILES['upload']['name'];
               $disc_manager = $environment->getDiscManager();
               $disc_manager->copyFile($_FILES['upload']['tmp_name'],$filename,true);
               $user_item->setPicture($filename);
               if ( isset($portal_user_item) ) {
                  if ( $disc_manager->copyImageFromRoomToRoom($filename,$portal_user_item->getContextID()) ) {
                     $value_array = explode('_',$filename);
                     $old_room_id = $value_array[0];
                     $old_room_id = str_replace('cid','',$old_room_id);
                     $value_array[0] = 'cid'.$portal_user_item->getContextID();
                     $new_picture_name = implode('_',$value_array);
                     $portal_user_item->setPicture($new_picture_name);
                  }
               }
            }

             if (isset($_POST['user_is_allowed_to_create_context'])) {
                 $user_item->setIsAllowedToCreateContext($_POST['user_is_allowed_to_create_context']);
                 if ( isset($portal_user_item) ) {
                     $portal_user_item->setIsAllowedToCreateContext($_POST['user_is_allowed_to_create_context']);
                 }
             }

             if (isset($_POST['user_is_allowed_to_use_caldav'])) {
                 $user_item->setIsAllowedToUseCalDAV($_POST['user_is_allowed_to_use_caldav']);
                 if ( isset($portal_user_item) ) {
                     $portal_user_item->setIsAllowedToUseCalDAV($_POST['user_is_allowed_to_use_caldav']);
                 }
             }


            #########################################################
            # Gruppen können im Formular nicht mehr gesetzt werden
            #########################################################
            /*
            // group
            $group_array = array();

            // now form post
            if (isset($_POST[CS_GROUP_TYPE])) {
               $group_array = $_POST[CS_GROUP_TYPE];
            }

            // add group id of group ALL add to group array
            // a user can not sign off the group all
            $label_manager = $environment->getLabelManager();
            $label_manager->setTypeLimit(CS_GROUP_TYPE);
            $label_manager->setContextLimit($environment->getCurrentContextID());
            $label_manager->setExactNameLimit('ALL');
            $label_manager->select();
            $label_list = $label_manager->get();
            if (!$label_list->isEmpty()) {
               $label = $label_list->getFirst();
               $label->setTitle('ALL');
               $group_array[] = $label->getItemID();
            }

            $user_item->setGroupListByID($group_array);
            */
            #########################################################
            # Gruppen können im Formular nicht mehr gesetzt werden
            #########################################################
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
               $id_array = array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids'));
               if (!$environment->inCommunityRoom()){
                   $user_item->setLinkedItemsByID(CS_GROUP_TYPE,$id_array);
               }
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            }

            if (isset($_POST['want_mail_get_account'])) {
               $user_item->setAccountWantMail($_POST['want_mail_get_account']);
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $user_item->setModificatorItem($user);
            $user_item->setModificationDate(getCurrentDateTimeInMySQL());
            if ( isset($portal_user_item) ) {
               $portal_user_item->setModificatorItem($user);
               $portal_user_item->setModificationDate(getCurrentDateTimeInMySQL());
            }

            // email visibility
            if (isset($_POST['email_visibility']) and !empty($_POST['email_visibility'])) {
               $user_item->setEmailNotVisible();
            } else {
               $user_item->setEmailVisible();
            }

            // unset user has to change email-address
            $unsetHasToChangeEmail = false;
            if (isset($_POST['email'])) {
               if ($user_item->hasToChangeEmail()) {
                  $user_item->unsetHasToChangeEmail();
                  $unsetHasToChangeEmail = true;
               }
            }

            // save user
            $user_item->save();
            if ( isset($portal_user_item) ) {
               $portal_user_item->save();
            }

            if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_search') ) {
               $user_search = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_search');
            } else {
               $user_search = '';
            }
            if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_from') ) {
               $user_from = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_from');
            } else {
               $user_from = 1;
            }
            $user_manager->setContextLimit($environment->getCurrentContextID());
            $user_manager->setSearchLimit($user_search);
            $user_manager->setIntervalLimit($user_from-1,CS_LIST_INTERVAL);
            $user_manager->setUserLimit();
            $user_manager->select();
            $user_ids = $user_manager->getIDArray();       // returns an array of item ids
            $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_ids', $user_ids);
            $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_search', $user_search);
            $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_from', $user_from);

            if ( $old_firstname != $user_item->getFirstname()
                 or $old_lastname != $user_item->getLastname()
                 or isset($_POST['title_change_all'])
                 or isset($_POST['street_change_all'])
                 or isset($_POST['zipcode_change_all'])
                 or isset($_POST['city_change_all'])
                 or isset($_POST['room_change_all'])
                 or isset($_POST['telephone_change_all'])
                 or isset($_POST['birthday_change_all'])
                 or isset($_POST['cellularphone_change_all'])
                 or isset($_POST['homepage_change_all'])
                 or isset($_POST['organisation_change_all'])
                 or isset($_POST['position_change_all'])
                 or isset($_POST['email_change_all'])
                 or isset($_POST['messenger_change_all'])
                 or isset($_POST['description_change_all'])
                 or isset($_POST['picture_change_all'])
                 or $unsetHasToChangeEmail) {
               // change firstname and lastname in all other user_items of this user
               $user_manager = $environment->getUserManager();
               $dummy_user = $user_manager->getNewItem();
               if ($old_firstname != $user_item->getFirstname()) {
                  $dummy_user->setFirstName($user_item->getFirstname());
               }
               if ($old_lastname != $user_item->getLastname()) {
                  $dummy_user->setLastName($user_item->getLastname());
               }
               if (isset($_POST['title_change_all'])) {
                  $value = $user_item->getTitle();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setTitle($value);
               }
               if (isset($_POST['street_change_all'])) {
                  $value = $user_item->getStreet();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setStreet($value);
               }
               if (isset($_POST['zipcode_change_all'])) {
                  $value = $user_item->getZipCode();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setZipCode($value);
               }
               if (isset($_POST['city_change_all'])) {
                  $value = $user_item->getCity();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setCity($value);
               }
               if (isset($_POST['room_change_all'])) {
                  $value = $user_item->getRoom();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setRoom($value);
               }
               if (isset($_POST['telephone_change_all'])) {
                  $value = $user_item->getTelephone();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setTelephone($value);
               }
               if (isset($_POST['birthday_change_all'])) {
                  $value = $user_item->getBirthday();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setBirthday($value);
               }
               if (isset($_POST['cellularphone_change_all'])) {
                  $value = $user_item->getCellularphone();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setCellularphone($value);
               }
               if (isset($_POST['homepage_change_all'])) {
                  $value = $user_item->getHomepage();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setHomepage($value);
               }
               if (isset($_POST['organisation_change_all'])) {
                  $value = $user_item->getOrganisation();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setOrganisation($value);
               }
               if (isset($_POST['position_change_all'])) {
                  $value = $user_item->getPosition();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setPostion($value);
               }
               if (isset($_POST['messenger_change_all'])) {
                  $value = $user_item->getICQ();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setICQ($value);
                  $value = $user_item->getSkype();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setSkype($value);
                  $value = $user_item->getYahoo();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setYahoo($value);
                  $value = $user_item->getMSN();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setMSN($value);
                  $value = $user_item->getJabber();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setJabber($value);
               }
               if (isset($_POST['email_change_all']) || $unsetHasToChangeEmail) {
                  $value = $user_item->getEmail();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setEmail($value);

                  if (!$user->isEmailVisible()) {
                     $dummy_user->setEmailNotVisible();
                  } else {
                     $dummy_user->setEmailVisible();
                  }
               }
               if (isset($_POST['description_change_all'])) {
                  $value = $user_item->getDescription();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setDescription($value);
               }
               if (isset($_POST['picture_change_all'])) {
                  $value = $user_item->getPicture();
                  if (empty($value)) {
                     $value = -1;
                  }
                  $dummy_user->setPicture($value);
               }
               $user_item->changeRelatedUser($dummy_user);
            }

            $user_manager = $environment->getUserManager();
            $dummy_user = $user_manager->getNewItem();
            if (isset($_POST['user_is_allowed_to_create_context'])) {
                $dummy_user->setIsAllowedToCreateContext($_POST['user_is_allowed_to_create_context']);
            }

            if (isset($_POST['user_is_allowed_to_use_caldav'])) {
                $dummy_user->setIsAllowedToUseCalDAV($_POST['user_is_allowed_to_use_caldav']);
            }

            $user_item->changeRelatedUser($dummy_user);

            //Add modifier to all users who ever edited this item
            $manager = $environment->getLinkModifierItemManager();
            $manager->markEdited($user->getItemID());
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            $session->unsetValue('linked_items_post_vars');

            // redirect
            $params = array();
            $params['iid'] = $user_item->getItemID();
            redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),'detail', $params);
         }
      }

      // display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      $form_view->setItem($user_item);
      unset($class_params);
      $room_item = $environment->getCurrentContextItem();
      // Define rubric connections
         $rubric_connection = array();
         $current_rubrics = $room_item->getAvailableRubrics();
         foreach ( $current_rubrics as $rubric ) {
            switch ( $rubric ) {
               case CS_GROUP_TYPE:
                  $rubric_connection[] = CS_GROUP_TYPE;
                  break;
            }
      }
      $form_view->setRubricConnections($rubric_connection);
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),'edit',''));
      if (!$user_item->mayEditRegular($current_user)) {
         $form_view->warnChanger();
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $params['width'] = 500;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->add($errorbox);
      }
      $form_view->setForm($form);
      if( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($form_view);
      }else{
          $page->add($form_view);
      }
   }
}
?>