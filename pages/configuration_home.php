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
   $form = $class_factory->getClass(CONFIGURATION_HOME_FORM,array('environment' => $environment));
   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_HOME_FORM_VIEW,$params);
   unset($params);

   // Save item
   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
               if ($room_item->isPrivateRoom()){
                  $time = '7';
                  if (!empty($_POST['time_spread'])){
                     $time = $_POST['time_spread'];
                     if ($time =='1'){
                        $value = '1';
                     }elseif ($time =='3'){
                        $value = '30';
                     }else {
                        $value = '7';
                     }
               $room_item->setTimeSpread($value);
                  }
               }elseif (!empty($_POST['time_spread'])) {
            $room_item->setTimeSpread($_POST['time_spread']);
         }
         if (!empty($_POST['home_status'])) {
            if ($_POST['home_status']=='2'){
               $room_item->setHomeStatus('detailed');
            }else{
               $room_item->setHomeStatus('normal');
            }
         }
         if ( !$environment->inPrivateRoom() ){
            $current_room_modules = array();
            $room_item = $environment->getCurrentContextItem();
            $home_conf = $room_item->getHomeConf();
            $home_conf_array = explode(',',$home_conf);
            $current_room_modules = array();
            foreach ($home_conf_array as $rubric_conf) {
            $rubric_conf_array = explode('_',$rubric_conf);
            if ($rubric_conf_array[1] != 'none') {
               if ( !empty($_POST[$rubric_conf_array[0]]) ){
                  $current_room_modules[] = $rubric_conf_array[0].'_'.$_POST[$rubric_conf_array[0]];
               } else{
                  $current_room_modules[] = $rubric_conf_array[0].'_'.$rubric_conf_array[1];
               }
            } else {
               $current_room_modules[] = $rubric_conf_array[0].'_'.$rubric_conf_array[1];
            }
         }
         $room_item->setHomeConf(implode($current_room_modules,','));


            $current_room_right_modules = array();
            if (isset($_POST['activity']) and !empty($_POST['activity'])){
               $current_room_right_modules[] = 'activity'.'_'.$_POST['activity'];
            }
            if (isset($_POST['search']) and !empty($_POST['search'])){
               $current_room_right_modules[] = 'search'.'_'.$_POST['search'];
            }
            if (isset($_POST['homeextratools']) and !empty($_POST['homeextratools'])){
               $current_room_right_modules[] = 'homeextratools'.'_'.$_POST['homeextratools'];
            }
            if (isset($_POST['actions']) and !empty($_POST['actions'])){
               $current_room_right_modules[] = 'actions'.'_'.$_POST['actions'];
            }
            if (isset($_POST['usageinfos']) and !empty($_POST['usageinfos'])){
               $current_room_right_modules[] = 'usageinfos'.'_'.$_POST['usageinfos'];
            }
            if (isset($_POST['preferences']) and !empty($_POST['preferences'])){
               $current_room_right_modules[] = 'preferences'.'_'.$_POST['preferences'];
             }
            if (isset($_POST['buzzwords']) and !empty($_POST['buzzwords'])){
               $current_room_right_modules[] = 'buzzwords'.'_'.$_POST['buzzwords'];
             }
            if (isset($_POST['tags']) and !empty($_POST['tags'])){
               $current_room_right_modules[] = 'tags'.'_'.$_POST['tags'];
             }
            $room_item->setHomeRightConf(implode($current_room_right_modules,','));
      }
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