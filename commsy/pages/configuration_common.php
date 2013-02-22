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

// function for page edit
// - to check files for virus
if (isset($c_virus_scan) and $c_virus_scan) {
   include_once('functions/page_edit_functions.php');
}


// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($room_iid) {
   global $session;
   $session->unsetValue($room_iid.'_add_community_rooms');
}


// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();

// get iid
if ( !empty($_GET['iid']) ) {
   $room_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $room_iid = $_POST['iid'];
}

// Get item to be edited
$room_manager = $environment->getRoomManager();
if(!empty($room_iid)) {
   $room_item = $room_manager->getItem($room_iid);
} else {
   redirect($environment->getCurrentContextID(),'home','index');
}
if (!$current_user->isModerator() and !$room_item->mayEdit($current_user)) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->addWarning($errorbox);
} else {

   //access granted
   $with_anchor = false;

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session = $environment->getSessionItem();
      $history = $session->getValue('history');
      if ( !isset($history[1]['function']) ) {
         redirect($environment->getCurrentContextID(),'home','index',array());
      } elseif ($history[1]['function'] != 'common') {
         redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
      } else {
         redirect($history[2]['context'],$history[2]['module'],$history[2]['function'],$history[2]['parameter']);
      }
   }

   // delete item
   elseif ( isOption($command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
      $room_item->delete();
      redirect($environment->getCurrentPortalID(),'home','index','');
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_COMMON_FORM,array('environment' => $environment));

      // Add a community_room
      if ( isOption($command, $translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON')) ) {
         $focus_element_onload = 'communityrooms';
         $post_community_room_ids = array();
         $new_community_room_ids = array();
         $new_buzzword_ids = array();
         if ( isset($_POST['communityroomlist']) ) {
            $post_community_room_ids = $_POST['communityroomlist'];
         }

         if ( $session->issetValue($room_iid.'_add_community_rooms') ) {
            $community_room_array = $session->getValue($room_iid.'_add_community_rooms');
         } else {
            $community_room_array = array();
         }
         if ( !empty($_POST['communityrooms']) and $_POST['communityrooms']!=-1 and !in_array($_POST['communityrooms'],$post_community_room_ids) ) {
            $temp_array = array();
            $community_manager = $environment->getCommunityManager();
            $community_manager->reset();
            $community_item = $community_manager->getItem($_POST['communityrooms']);

            $temp_array['name'] = $community_item->getTitle();
            $temp_array['id'] = $community_item->getItemID();
            $community_room_array[] = $temp_array;
            $new_community_room_ids[] = $temp_array['id'];
         }

         if ( count($community_room_array) > 0 ) {
            $session->setValue($room_iid.'_add_community_rooms', $community_room_array);
         } else {
            $session->unsetValue($room_iid.'_add_community_rooms');
         }
         $post_community_room_ids = array_merge($post_community_room_ids, $new_community_room_ids);
      }

      // Create data for a new material
      elseif ( $room_iid == 'NEW') {
         cleanup_session($room_iid);
      }


      // Load form data from postvars
      if ( !empty($_POST) ) {
         if ( !empty($_FILES) ) {
            if ( !empty($_FILES['logo']['tmp_name']) ) {
               $new_temp_name = $_FILES['logo']['tmp_name'].'_TEMP_'.$_FILES['logo']['name'];
               move_uploaded_file($_FILES['logo']['tmp_name'],$new_temp_name);
               $_FILES['logo']['tmp_name'] = $new_temp_name;
               $session_item = $environment->getSessionItem();
               if ( isset($session_item) ) {
                  $session_item->setValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_temp_name',$new_temp_name);
                  $session_item->setValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_name',$_FILES['logo']['name']);
               }
            }
            $values = array_merge($_POST,$_FILES);
         } else {
            $values = $_POST;
         }
         if ( isset($post_community_room_ids) AND !empty($post_community_room_ids) ) {
            $values['communityroomlist'] = $post_community_room_ids;
         }
         $form->setFormPost($values);
      }

      // Load form data from database
      elseif ( isset($room_item) ) {
         $form->setItem($room_item);
         if ( $room_item->isProjectRoom() ) {
            $community_room_list = $room_item->getCommunityList();
               if ( !$community_room_list->isEmpty() ) {
                  $community_room_array = array();
                  $community_room_item = $community_room_list->getFirst();
                  while ( $community_room_item ) {
                     $temp_array = array();
                     $temp_array['name'] = $community_room_item->getTitle();
                     $temp_array['id'] = (int)$community_room_item->getItemID();
                     $community_room_array[] = $temp_array;
                     $community_room_item = $community_room_list->getNext();
                  }
                  if ( !empty($community_room_array)) {
                     $session->setValue($room_iid.'_add_community_rooms', $community_room_array);
                  }
               }
            }
         }

      if ($session->issetValue($room_iid.'_add_community_rooms')) {
         $form->setSessionCommunityRoomArray($session->getValue($room_iid.'_add_community_rooms'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command)
           and ( isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
                 or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
               )
         ) {
   if ( $form->check()
        and empty($_FILES['logo']['tmp_name'])
        and !empty($_POST['hidden_logo_name'])
      ) {
      $session_item = $environment->getSessionItem();
      if ( isset($session_item) ) {
         $_FILES['logo']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_temp_name');
         $_FILES['logo']['name']     = $session_item->getValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_name');
         $session_item->unsetValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_temp_name');
         $session_item->unsetValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_name');
      }
   }
   if ( $form->check()
        and ( !isset($c_virus_scan)
              or !$c_virus_scan
              or page_edit_virusscan_isClean($_FILES['logo']['tmp_name'],$_FILES['logo']['name'])
            )
      ) {

      // Set modificator and modification date
      $room_item->setModificatorItem($environment->getCurrentUserItem());
      $room_item->setModificationDate(getCurrentDateTimeInMySQL());

      // Set attributes
      if ( isset($_POST['title']) ) {
         $room_item->setTitle($_POST['title']);
      }

      // logo: save and/or delete current logo
      if ( isset($_POST['delete_logo']) ) {
      $disc_manager = $environment->getDiscManager();
      if ( $disc_manager->existsFile($room_item->getLogoFilename()) ) {
         $disc_manager->unlinkFile($room_item->getLogoFilename());
      }
           $room_item->setLogoFilename('');
      }
      if ( !empty($_FILES['logo']['name']) ) {
         $logo = $room_item->getLogoFilename();
         $disc_manager = $environment->getDiscManager();
         if ( !empty ($logo) ) {
       if ( $disc_manager->existsFile($room_item->getLogoFilename()) ) {
          $disc_manager->unlinkFile($room_item->getLogoFilename());
       }
       $room_item->setLogoFilename('');
         }
         $filename = 'cid'.$room_item->getItemID().'_logo_'.$_FILES['logo']['name'];
         $disc_manager->setContextID($room_item->getItemID());
         $disc_manager->copyFile($_FILES['logo']['tmp_name'],$filename,true);
         $disc_manager->setContextID($environment->getCurrentContextID());
         $room_item->setLogoFilename($filename);
      }


      $description = $room_item->getDescription();
      if (!empty($_POST['description'])) {
         $description = $_POST['description'];
      }
      $room_item->setDescription($description);

      $community_room_array = array();
      if ( isset($_POST['communityroomlist']) ) {
         $community_room_array = $_POST['communityroomlist'];
      }
      if ( isset($_POST['communityrooms']) and !in_array($_POST['communityrooms'],$community_room_array) and $_POST['communityrooms'] > 0) {
         $community_room_array[] = $_POST['communityrooms'];
      }
      if ( $room_item->isProjectRoom() ) {
         $room_item->setCommunityListByID($community_room_array);
            }

      // time (clock pulses)
      if (isset($_POST['time2']) and !empty($_POST['time2'])) {
         if (in_array('cont',$_POST['time2'])) {
            $room_item->setContinuous();
         } else {
            $room_item->setTimeListByID($_POST['time2']);
            $room_item->setNotContinuous();
         }
      } elseif ($room_item->isProjectRoom()) {
         $room_item->setTimeListByID(array());
         $room_item->setNotContinuous();
      }

      // Save item
      $room_item->save();

      // Redirect
      $session = $environment->getSessionItem();
      $history = $session->getValue('history');
      if ($history[1]['function'] != 'common') {
         redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
      } else {
         redirect($history[2]['context'],$history[2]['module'],$history[2]['function'],$history[2]['parameter']);
      }
   }
      }

      // display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$params);
      unset($params);
      if (isset($room_item) and !$room_item->mayEditRegular($current_user)) {
         $form_view->warnChanger();
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $params['width'] = 500;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->addWarning($errorbox);
      }

      include_once('functions/curl_functions.php');
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      $page->addForm($form_view);
   }
}
// room list on the left side
include_once('classes/cs_guide_room_list_page.php');
$guide_room_list_page = new cs_guide_room_list_page($environment,true);
$page->addRoomList($guide_room_list_page->getViewObject());
?>