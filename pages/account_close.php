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

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}


if (!empty($_GET['iid'])) {
   $iid = $_GET['iid'];
} elseif (!empty($_POST['iid'])) {
   $iid = $_POST['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('No user selected!',E_USER_ERROR);
}

$user_manager = $environment->getUserManager();
$user_item = $user_manager->getItem($iid);
$room_item = $environment->getCurrentContextItem();

// Check access rights
if (!empty($iid) and $iid != 'NEW') {
   $current_user = $environment->getCurrentUserItem();
   if (!$user_item->getItemID() == $current_user->getItemID()) { // only user should be allowed to edit her/his own account
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

if ($command != 'error') { // only if user is allowed to edit user
   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(USER_CLOSE_FORM,$class_params);
   unset($class_params);
   $form->prepareForm();
   $form->loadValues();

   // cancel edit process
   if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
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
      $redirect = false;
      if ( isOption($command,$translator->getMessage('COMMON_USER_REJECT_BUTTON')) ) {
         $user_item->reject();
         $user_item->save();
         $redirect = true;
      }
      if ( isOption($command,$translator->getMessage('COMMON_USER_AND_ENTRIES_DELETE_BUTTON')) ) {
         $user_item->deleteAllEntriesOfUser();
         $user_item->delete();
         $redirect = true;
      }
     // redirect
     if ( $redirect ) {
        ################################
        # FLAG: group room
        ################################
        if ( $environment->inGroupRoom() ) {
           $current_context = $environment->getCurrentContextItem();
           $current_user = $environment->getCurrentUserItem();
           $group_item = $current_context->getLinkedGroupItem();
           if ( isset($group_item) and !empty($group_item) ) {
              $project_room_item = $current_context->getLinkedProjectItem();
              if ( isset($project_room_item) and !empty($project_room_item) ) {
                 $project_room_user_item = $project_room_item->getUserByUserID($current_user->getUserID(),$current_user->getAuthSource());
                 $group_item->removeMember($project_room_user_item);

                 $params = array();
                 $params['iid'] = $group_item->getItemID();
                 redirect($project_room_item->getItemID(), CS_GROUP_TYPE, 'detail', $params);
              }
           }
        }
        ################################
        # FLAG: group room
        ################################

        $params = array();
        $params['room_id'] = $environment->getCurrentContextID();
        redirect($environment->getCurrentPortalID(), 'home', 'index', $params);
      }

      // display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$params);
      unset($params);
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),'close',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>