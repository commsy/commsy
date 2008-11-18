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

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$is_saved = false;

// get iid
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = $environment->getCurrentContextID();
}

// hier muss auf den aktuellen Kontext referenziert werden,
// da sonst sp�ter diese Einstellung wieder �berschrieben wird
// in der commsy.php beim Speichern der Aktivit�t
$current_context_item = $environment->getCurrentContextItem();
if ($current_iid == $current_context_item->getItemID()) {
   $item = $current_context_item;
} else {
   if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
      $room_manager = $environment->getRoomManager();
   } elseif ($environment->inPortal()) {
      $room_manager = $environment->getPortalManager();
   }
   $item = $room_manager->getItem($current_iid);
}

// Check access rights
if ( isset($item) and !$item->mayEdit($current_user) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}

elseif (isset($item) and !$item->withHomepageLink()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}

// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Initialize the form
   include_once('classes/cs_configuration_homepage_form.php');
   $form = new cs_configuration_homepage_form($environment);
   // display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   }

    // Load form data from database
   elseif ( isset($item) ) {
      $form->setItem($item);
   }

   $form->prepareForm();
   $form->loadValues();

   // Save item
   if ( !empty($command) and
        (isOption($command, getMessage('COMMON_SAVE_BUTTON'))
         or isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) ) ) {

      if ( $form->check() ) {

         // Set modificator and modification date
         $current_user = $environment->getCurrentUserItem();
         $item->setModificatorItem($current_user);
         $item->setModificationDate(getCurrentDateTimeInMySQL());

         // homepage link
         if (isset($_POST['homepage_new']) and !empty($_POST['homepage_new']) ) {
            // delete old homepage
            $homepage_manager = $environment->getHomepageManager();
            $homepage_manager->deleteHomepage($item->getItemID());
         }

         if ( isset($_POST['homepagelink'])
              and !empty($_POST['homepagelink'])
              and $_POST['homepagelink'] == 1
              and !$item->isHomepageLinkActive()
            ) {
            $item->setHomepageLinkActive();

            // create new homepage
            $homepage_manager = $environment->getHomepageManager();
            $homepage_manager->initHomepage($item->getItemID());

         } else {
            $item->setHomepageLinkInactive();
         }

         if ( isset($_POST['homepage_desc_link']) and $_POST['homepage_desc_link'] ) {
            $item->activateHomepageDescLink();
         } else {
            $item->deactivateHomepageDescLink();
         }

         // Save item
         $item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }

   if (isset($item) and !$item->mayEditRegular($current_user)) {
      $form_view->warnChanger();
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $params['width'] = 500;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText(getMessage('COMMON_EDIT_AS_MODERATOR'));
      $page->add($errorbox);
   }

   include_once('functions/curl_functions.php');
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
    if ( $environment->inPortal() or $environment->inServer() ){
       $page->addForm($form_view);
    } else {
       $page->add($form_view);
    }
}
?>