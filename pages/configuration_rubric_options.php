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
} elseif ( !$room_item->isOpen() and !$room_item->isTemplate() ) {
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
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(CONFIGURATION_RUBRIC_OPTIONS_FORM,$class_params);
   unset($class_params);
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   if ( !empty($command) and isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
     redirect($environment->getCurrentContextID(),'configuration', 'index', '');
   }
   // Save item
   elseif ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }
      $correct = $form->check();
      if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
         $home_conf = $room_item->getHomeConf();
         $home_conf_array = explode(',',$home_conf);
         $current_room_modules = array();
         foreach ($home_conf_array as $rubric_conf) {
            $rubric_conf_array[] = explode('_',$rubric_conf);
         }

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
         $temp_array = array();
         $j = 0;
         if ( !empty($_POST['rubric_0']) ) {
            $count = 0;
            while ( isset($_POST['rubric_'.$count]) ) {
               $count++;
            }
         } else {
            $default_rubrics = $room_item->getAvailableDefaultRubricArray();
            if ( count($default_rubrics) > 8 ) {
               $count = 8;
            } else {
               $count = count($default_rubrics);
            }
         }
         $rubric_array_for_plugin = array();
         for ($i=0; $i<$count; $i++){
            $rubric = '';
            if (!empty($_POST['rubric_'.$i])){
               if ($_POST['rubric_'.$i] != 'none') {
                  $rubric_array_for_plugin[] = $_POST['rubric_'.$i];
                  $temp_array[$i] = $_POST['rubric_'.$i].'_';
                  if ( !empty($_POST['show_'.$i]) ) {
                     $temp_array[$i] .= $_POST['show_'.$i];
                  } else {
                     $temp_array[$i] .= 'nodisplay';
                  }
                  $j++;
               }
            }
         }
         $room_item->setHomeConf(implode($temp_array,','));

         // plugins
         $plugin_list = $environment->getRubrikPluginClassList($environment->getCurrentPortalID());
         if ( isset($plugin_list)
              and $plugin_list->isNotEmpty()
            ) {
            $plugin_class = $plugin_list->getFirst();
            while ( $plugin_class ) {
               if (in_array(mb_strtolower($plugin_class->getIdentifier()),$rubric_array_for_plugin)) {
                  $room_item->setPluginOn(mb_strtolower($plugin_class->getIdentifier()));
               } else {
                  $room_item->setPluginOff(mb_strtolower($plugin_class->getIdentifier()));
               }
               $plugin_class = $plugin_list->getNext();
            }
         }

         // save room_item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
         if (!isset($_GET['option'])){
            $params['conf'] = implode($temp_array,',');
            $params['saved'] = 'true';
            redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
         }
      }
   }
   
   if(isset($_GET['saved']) && $_GET['saved'] == 'true') {
      $form_view->setItemIsSaved();
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
   $page->add($form_view);
}
?>