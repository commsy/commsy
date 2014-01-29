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
   } elseif ( isset($_GET['option']) ) {
      $command = $_GET['option'];
   }else {
      $command = '';
   }

   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_RUBRIC_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_RUBRIC_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command) and isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ( $correct and isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON')) ) {
         $home_conf = $room_item->getHomeConf();
         $home_conf_array = explode(',',$home_conf);
         $current_room_modules = array();
         foreach ($home_conf_array as $rubric_conf) {
         $rubric_conf_array[] = explode('_',$rubric_conf);
      }
      $temp_array = array();
      $j = 0;
      $count = 8;
      if ( $room_item->isCommunityRoom()
           or $room_item->isGroupRoom()
         ) {
         $count = 7;
      }
      for ($i=0; $i<$count; $i++){
         $rubric = '';
         if (!empty($_POST['rubric_'.$i])){
         if ($_POST['rubric_'.$i] != 'none') {
            $temp_array[$j][0] = $_POST['rubric_'.$i];
            $found = false;
            foreach ($rubric_conf_array as $rubric_conf) {
              if ($rubric_conf[0] == $_POST['rubric_'.$i]){
                 if ($rubric_conf[1] != 'none' and !empty($rubric_conf[1])) {
                    $temp_array[$j][1] = $rubric_conf[1];
                 } else {
                      $temp_array[$j][1] = 'tiny';
      }
      $found = true;
              }
            }
            if (!$found) {
               $temp_array[$j][1] = 'tiny';
            }
            $j++;
         }
         }elseif(!empty($_GET['rubric_'.$i])){
         if ($_GET['rubric_'.$i] != 'none') {
            $temp_array[$j][0] = $_GET['rubric_'.$i];
            $found = false;
            foreach ($rubric_conf_array as $rubric_conf) {
              if ($rubric_conf[0] == $_GET['rubric_'.$i]){
                 if ($rubric_conf[1] != 'none' and !empty($rubric_conf[1])) {
                    $temp_array[$j][1] = $rubric_conf[1];
                 } else {
                      $temp_array[$j][1] = 'tiny';
      }
      $found = true;
              }
            }
            if (!$found) {
               $temp_array[$j][1] = 'tiny';
            }
            $j++;
         }
         }
      }
      foreach ($rubric_conf_array as $rubric_conf) {
         $temp_array2 = $temp_array;
         $boolean = false;
         foreach($temp_array2 as $entry){
            if ($rubric_conf[0] == $entry[0]){
               $boolean = true;
            }
         }
         if (!$boolean){
            $temp_array[$j][0] = $rubric_conf[0];
            $temp_array[$j][1] = 'none';
            $j++;
         }
      }
      $current_room_modules = array();
      foreach($temp_array as $entry){
         $current_room_modules[]=$entry[0].'_'.$entry[1];
      }
      $room_item->setHomeConf(implode($current_room_modules,','));
      // save room_item
      $room_item->save();
      $form_view->setItemIsSaved();
      $is_saved = true;
      if (!isset($_GET['option'])){
         $params = $_POST;
         redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
      }
      }
   }

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   } elseif ( isset($room_item) ) {
      $form->setItem($room_item);
   }
      $form->prepareForm();
      $form->loadValues();


   // Display form
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if (!$form->check()){
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $params['width'] = 500;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('CONFIGURATION_RUBRIC_ERROR_DESCRIPTION'));
      $page->add($errorbox);
   }
   $page->add($form_view);
}
?>