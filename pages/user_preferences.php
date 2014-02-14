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

// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_GET['iid'])) {
   $iid = $_GET['iid'];
} elseif (!empty($_POST['iid'])) {
   $iid = $_POST['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('No user selected!',E_USER_ERROR);
}

$current_module = $environment->getCurrentModule();

// Check access rights
$current_user = $environment->getCurrentUserItem();
$user_manager = $environment->getUserManager();
$user = $user_manager->getItem($iid);
if (!$user->mayEdit($current_user)) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = $translator->getMessage('LOGIN_NOT_ALLOWED').'<br />';
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') { // only if user is allowed to edit user
   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(USER_PREFERENCES_FORM,$class_params);
   unset($class_params);

   if (empty($command)) {
      if ( !empty($user) ) {
         $user_manager = $environment->getUserManager();
         $user = $user_manager->getItem($iid);
         unset($user_manager);
      }
      $form->setItem($user);
      $form->prepareForm();
      $form->loadValues();
   }

   // cancel edit process
   if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      if ( empty($_POST['iid']) ) {    // cancel new user item
         redirect($environment->getCurrentContextID(), $current_module, 'index', '');
      } else {                                  // cancel edit news item
         $params = array();
         $params['iid'] = $_POST['iid'];
         redirect($environment->getCurrentContextID(),
                  $environment->getCurrentModule(),
                  'detail',
                  $params);
      }
   } elseif ( isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON')) ) {  //save

      if (!empty($_POST)) {  // second call of form: set post vars
         $form->setFormPost($_POST);
      }
      $form->prepareForm();
      $form->loadValues();

      if ( isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON')) ) {
         // Save changes, if everything is okay
         if ( $form->check()  ) {
            $user_manager = $environment->getUserManager();
            $user = $user_manager->getItem($_POST['iid']);

            if ( !empty($_POST['commsy_visible']) ) {
               if ($_POST['commsy_visible'] == 1) {
                  $user->setVisibleToLoggedIn();
               } elseif ($_POST['commsy_visible'] == 2) {
                  $user->setVisibleToAll();
               }
            }

            if (!empty($_POST['language'])) {
               $user->setLanguage($_POST['language']);
            }

            if (isset($_POST['want_mail_get_account'])) {
               $user->setAccountWantMail($_POST['want_mail_get_account']);
            }
            if (isset($_POST['want_mail_publish_material'])) {
               $user->setPublishMaterialWantMail($_POST['want_mail_publish_material']);
            }
            if (isset($_POST['want_mail_open_room'])) {
               $user->setOpenRoomWantMail($_POST['want_mail_open_room']);
            }
            if ( !empty($_POST['autosave_status']) ) {
               if ($_POST['autosave_status'] == 'yes') {
                  $user->turnAutoSaveOn();
               } elseif ($_POST['autosave_status'] == 'no') {
                  $user->turnAutoSaveOff();
               }
            }

            // Set modificator and modification date
            $user->setModificatorItem($environment->getCurrentUserItem());
            $user->setModificationDate(getCurrentDateTimeInMySQL());
            $user->save();

            if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_search') ) {
               $user_search = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_search');
            } elseif ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_search') ) {
               $user_search = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_search');
            } else {
               $user_search = '';
            }
            if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_from') ) {
               $user_from = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_from');
            } elseif ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_from') ) {
               $user_from = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_from');
            } else {
               $user_from = 1;
            }
            if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_sortby') ) {
               $user_sortby = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_sortby');
            } elseif ( $session->issetValue('cid'.$environment->getCurrentContextID().'_user_index_sortby') ) {
               $user_sortby = $session->getValue('cid'.$environment->getCurrentContextID().'_user_index_sortby');
            } else {
               $user_sortby = '';
            }
            $user_manager->setContextLimit($environment->getCurrentContextID());
            $user_manager->setSearchLimit($user_search);
            $user_manager->setUserLimit();
            if ( $current_user->isUser() ) {
               $user_manager->setVisibleToAllAndCommsy();
            } else {
               $user_manager->setVisibleToAll();
            }
            $user_ids = $user_manager->getIDArray();       // returns an array of item ids
            if ( $environment->inCommunityRoom() ) {
               $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_ids', $user_ids);
               $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_search', $user_search);
               $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_from', $user_from);
            } else {
               $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_ids', $user_ids);
               $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_search', $user_search);
               $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_from', $user_from);
            }

            // Add modifier to all users who ever edited this item
            // TBD: should be in save methode of item
            $manager = $environment->getLinkModifierItemManager();
            $manager->markEdited($user->getItemID());

            $params = array();
            $params['iid'] = $_POST['iid'];
            redirect($environment->getCurrentContextID(),
                     $environment->getCurrentModule(),
                     'detail',
                     $params);
         }
      }
   }
   $class_params = array();
   $class_params['environment'] = $environment;
   $class_params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
   unset($class_params);
   $form_view->setAction(curl($environment->getCurrentContextID(),$current_module,'preferences',''));
   if (!$user->mayEditRegular($current_user)) {
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
   $page->add($form_view);
}
?>