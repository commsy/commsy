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

// Get the translator object
$translator = $environment->getTranslationObject();

if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( $room_item->isProjectRoom() and !$room_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif (!$current_user->isModerator()) {
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

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_LISTVIEW_FORM,array('environment' => $environment));
   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
         if (!empty($_POST['length'])){
            $length = $_POST['length'];
            if ($length =='3'){
               $value = '50';
            }elseif ($length =='4'){
               $value = 'all';
            }else {
               $value = '20';
            }
            $room_item->setListLength($value);

            $session_item = $environment->getSessionItem();
            $session_item->unsetValue('interval');
            $environment->setSessionItem($session_item);
         }

         $current_list_right_modules = array();
         if (isset($_POST['actions']) and !empty($_POST['actions'])){
            $current_list_right_modules[] = 'actions'.'_'.$_POST['actions'];
         }
         if (isset($_POST['search']) and !empty($_POST['search'])){
            $current_list_right_modules[] = 'search'.'_'.$_POST['search'];
         }
         if (isset($_POST['buzzwords']) and !empty($_POST['buzzwords'])){
            $current_list_right_modules[] = 'buzzwords'.'_'.$_POST['buzzwords'];
         }
         if (isset($_POST['tags']) and !empty($_POST['tags'])){
             $current_list_right_modules[] = 'tags'.'_'.$_POST['tags'];
         }
         if (isset($_POST['usage']) and !empty($_POST['usage'])){
             $current_list_right_modules[] = 'usage'.'_'.$_POST['usage'];
         }
         $room_item->setListBoxConf(implode($current_list_right_modules,','));

         $current_detail_right_modules = array();
         if (isset($_POST['detailactions']) and !empty($_POST['detailactions'])){
            $current_detail_right_modules[] = 'detailactions'.'_'.$_POST['detailactions'];
         }
         if (isset($_POST['detailbuzzwords']) and !empty($_POST['detailbuzzwords'])){
            $current_detail_right_modules[] = 'detailbuzzwords'.'_'.$_POST['detailbuzzwords'];
         }
         if (isset($_POST['detailtags']) and !empty($_POST['detailtags'])){
             $current_detail_right_modules[] = 'detailtags'.'_'.$_POST['detailtags'];
         }
         if (isset($_POST['detailnetnavigation']) and !empty($_POST['detailnetnavigation'])){
            $current_detail_right_modules[] = 'detailnetnavigation'.'_'.$_POST['detailnetnavigation'];
         }
         $room_item->setDetailBoxConf(implode($current_detail_right_modules,','));

         // save room_item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }
   // Load form data from postvars
   if ( !empty($_POST)  and !$is_saved) {
      $form->setFormPost($_POST);
   } elseif ( isset($room_item) ) {
      $form->setItem($room_item);
   }
   $form->prepareForm();
   $form->loadValues();

   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   $page->add($form_view);
}
?>