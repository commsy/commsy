<?PHP
//
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

if (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
} elseif ( !$room_item->isOpen() and !$room_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
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
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(CONFIGURATION_WORKFLOW_FORM,$class_params);
   unset($class_params);
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Load form data from postvars
   if ( !empty($_POST) and !$is_saved) {
      $form->setFormPost($_POST);
   }

   // Load form data from database
   elseif ( isset($room_item) ) {
      $form->setItem($room_item);
   }
   
   // Save item
   if ( !empty($command) and isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
     redirect($environment->getCurrentContextID(),'configuration', 'index', '');
   }
   elseif ( !empty($command)
        and ( isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
             )
      ) {

      if ( $form->check() ) {

         $isset_workflow = false;
         
         if ( isset($_POST['workflow_trafic_light']) and !empty($_POST['workflow_trafic_light']) and $_POST['workflow_trafic_light'] == 'yes') {
            $room_item->setWithWorkflowTrafficLight();
            $isset_workflow = true;
         } else {
            $room_item->setWithoutWorkflowTrafficLight();
         }
         if ( isset($_POST['workflow_resubmission']) and !empty($_POST['workflow_resubmission']) and $_POST['workflow_resubmission'] == 'yes' ) {
            $room_item->setWithWorkflowResubmission();
            $isset_workflow = true;
         } else {
            $room_item->setWithoutWorkflowResubmission();
         }
         if ( isset($_POST['workflow_reader']) and !empty($_POST['workflow_reader']) and $_POST['workflow_reader'] == 'yes' ) {
            $room_item->setWithWorkflowReader();
            $isset_workflow = true;
         } else {
            $room_item->setWithoutWorkflowReader();
         }

         if ( isset($_POST['workflow_trafic_light_default']) and !empty($_POST['workflow_trafic_light_default'])) {
            $room_item->setWorkflowTrafficLightDefault($_POST['workflow_trafic_light_default']);
         }
         
         if ( isset($_POST['workflow_trafic_light_green_text']) and !empty($_POST['workflow_trafic_light_green_text'])) {
            $room_item->setWorkflowTrafficLightTextGreen($_POST['workflow_trafic_light_green_text']);
         }
         if ( isset($_POST['workflow_trafic_light_yellow_text']) and !empty($_POST['workflow_trafic_light_yellow_text'])) {
            $room_item->setWorkflowTrafficLightTextYellow($_POST['workflow_trafic_light_yellow_text']);
         }
         if ( isset($_POST['workflow_trafic_light_red_text']) and !empty($_POST['workflow_trafic_light_red_text'])) {
            $room_item->setWorkflowTrafficLightTextRed($_POST['workflow_trafic_light_red_text']);
         }
         
         if ( isset($_POST['workflow_reader_group']) and !empty($_POST['workflow_reader_group'])) {
            $room_item->setWithWorkflowReaderGroup();
         } else {
            $room_item->setWithoutWorkflowReaderGroup();
         }
         if ( isset($_POST['workflow_reader_person']) and !empty($_POST['workflow_reader_person'])) {
            $room_item->setWithWorkflowReaderPerson();
         } else {
            $room_item->setWithoutWorkflowReaderPerson();
         }
         
         if ( isset($_POST['workflow_resubmission_show_to']) and !empty($_POST['workflow_resubmission_show_to'])) {
            $room_item->setWorkflowReaderShowTo($_POST['workflow_resubmission_show_to']);
         }
         
         if ( isset($_POST['workflow_validity']) and !empty($_POST['workflow_validity']) and $_POST['workflow_validity'] == 'yes' ) {
            $room_item->setWithWorkflowValidity();
            $isset_workflow = true;
         } else {
            $room_item->setWithoutWorkflowValidity();
         }
         
         if($isset_workflow){
            $room_item->setWithWorkflow();
         } else {
            $room_item->setWithoutWorkflow();
         }
         
         // Save item
         $room_item->save();
         $form_view->setItemIsSaved();
         $is_saved = true;

      }
   }

   $form->prepareForm();
   $form->loadValues();

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