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

// Get the translator object
$translator = $environment->getTranslationObject();

$is_saved = false;
// Check access rights
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
   } elseif (isset($_POST['info_text']) ) {
      $command = $translator->getMessage('COMMON_CHOOSE_BUTTON');
   } else {
      $command = '';
   }

   // Show form and/or save item
   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_USAGEINFO_FORM,array('environment' => $environment));
   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   if ( isOption($command, $translator->getMessage('COMMON_CHOOSE_BUTTON')) ) {
      if ($_POST['info_text'] == 'home') {
         $values['info_text'] = 'home';
      } else {
         $default_rubrics = $room_item->getAvailableRubrics();
         $rubric_array = array();
         foreach ($default_rubrics as $rubric) {
            if ($_POST['info_text'] == $rubric ){
               $values['info_text'] = $rubric;
            }
         }
      }
      $values['show'] = true;
      $array = $room_item->_getExtra('USAGE_INFO');
      if ( !empty($array) and in_array($values['info_text'].'_no',$room_item->_getExtra('USAGE_INFO')) ){
         $values['show'] = false;
      }
      $values['title'] = $room_item->getUsageInfoHeaderForRubric($values['info_text']);
      $values['text'] = $room_item->getUsageInfoTextForRubricInForm($values['info_text']);
      $values['text_form'] = $room_item->getUsageInfoTextForRubricFormInForm($values['info_text']);
      // Load form data from postvars
      $form->setFormPost($values);
   }

   // Save item
   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ( $correct ) {
         $info_array = array();
        if (is_array($room_item->_getExtra('USAGE_INFO'))) {
            $info_array = $room_item->_getExtra('USAGE_INFO');
         }
         $do_not_show = false;
         if (!empty($_POST['info_text'])){
            if (empty($_POST['show'])) {
               $do_not_show = true;
            }
            if ( empty($info_array) and  $do_not_show ){
               $info_array[] = $_POST['info_text'];
               $room_item->setUsageInfoArray($info_array);
            }
            elseif ( !in_array($_POST['info_text'].'_no', $info_array) and $do_not_show ){
               array_push($info_array,$_POST['info_text'].'_no');
               $room_item->setUsageInfoArray($info_array);
            }
            elseif ( in_array($_POST['info_text'].'_no', $info_array) and  !$do_not_show ){
               $array[]=$_POST['info_text'].'_no';
               $new_array = array_diff($info_array,$array);
               $room_item->setUsageInfoArray($new_array);
            }
            if (! empty($_POST['title']) ){
               $room_item->setUsageInfoHeaderForRubric( $_POST['info_text'],  $_POST['title']);
            }
            if (! empty($_POST['text']) ){
               if ( mb_stristr($_POST['text'],'<!-- KFC TEXT -->') ){
                  $text = str_replace('<!-- KFC TEXT -->','',$_POST['text']);
               } else{
                  $text =  $_POST['text'];
               }
               $room_item->setUsageInfoTextForRubric( $_POST['info_text'],  $text);
            }else{
               $room_item->setUsageInfoTextForRubric( $_POST['info_text'],  '');
            }
         }
         $info_form_array = array();
         if (is_array($room_item->getUsageInfoFormArray())) {
            $info_form_array = $room_item->getUsageInfoFormArray();
         }
         $do_not_show_form = false;
         if (!empty($_POST['info_text'])){
            if (empty($_POST['text_form'])) {
               $do_not_show_form = true;
            }
            if ( empty($info_form_array) and  $do_not_show_form ){
               $info_form_array[] = $_POST['info_text'];
               $room_item->setUsageInfoFormArray($info_form_array);
            }
            elseif ( !in_array($_POST['info_text'].'_no', $info_form_array) and $do_not_show_form ){
               array_push($info_form_array,$_POST['info_text'].'_no');
               $room_item->setUsageInfoFormArray($info_form_array);
            }
            elseif ( in_array($_POST['info_text'].'_no', $info_form_array) and  !$do_not_show_form ){
               $array[]=$_POST['info_text'].'_no';
               $new_array = array_diff($info_form_array,$array);
               $room_item->setUsageInfoFormArray($new_array);
            }
            if (! empty($_POST['title']) ){
               $room_item->setUsageInfoHeaderForRubricForm( $_POST['info_text'],  $_POST['title']);
            }
            if (! empty($_POST['text_form']) ){
               if ( mb_stristr($_POST['text_form'],'<!-- KFC TEXT -->') ){
                  $text = str_replace('<!-- KFC TEXT -->','',$_POST['text']);
               } else{
                  $text =  $_POST['text_form'];
               }
               $room_item->setUsageInfoTextForRubricForm( $_POST['info_text'],  $text);
            }else{
               $room_item->setUsageInfoTextForRubricForm( $_POST['info_text'],  '');
            }
          if(!empty($_POST['show_global'])) {
             $room_item->setUsageInfoGlobal('true');
          } else {
             $room_item->setUsageInfoGlobal('false');
          }

         }
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
      $form->setFormPost($_POST);
      $form->setItem($room_item);
   }

   $form->prepareForm();
   $form->loadValues();
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   $page->add($form_view);
}
?>
