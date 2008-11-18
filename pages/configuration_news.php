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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Check access rights
if ($current_user->isGuest()) {
   redirect($room_item->getItemID(),'home','index','');
} elseif ( $room_item->isPortal() and !$room_item->isOpen() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif ( ($room_item->isPortal() and !$current_user->isModerator())
           or ($room_item->isServer() and !$current_user->isRoot())
         ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment,
                                      true );
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
      include_once('classes/cs_configuration_news_form.php');
      $form = new cs_configuration_news_form($environment);
      // Display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      } else {
       $form->setItem($room_item);
     }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and ( isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) ) ) {

         if ( $form->check() ) {

         if (!empty($_POST['title'])) {
            $room_item->setServerNewsTitle($_POST['title']);
         } else {
            $room_item->setServerNewsTitle('');
         }
         if (!empty($_POST['link'])) {
            $room_item->setServerNewsLink($_POST['link']);
         } else {
            $room_item->setServerNewsLink('');
         }
         if (!empty($_POST['text'])) {
            $room_item->setServerNewsText($_POST['text']);
         } else {
            $room_item->setServerNewsText('');
         }
         if ($_POST['show'] == 1) {
            $room_item->setShowServerNews();
         } elseif ($_POST['show'] == -1) {
            $room_item->setDontShowServerNews();
         }
            $room_item->save();
            $form_view->setItemIsSaved();
            $is_saved = true;
         }
      }

      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
}
?>