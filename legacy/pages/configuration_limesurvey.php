<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez, Johannes Schultze
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
} else{
   $current_context_item = $environment->getCurrentContextItem();
   $current_iid = $current_context_item->getItemID();
}

$item = $environment->getCurrentPortalItem();

// Check access rights
if ( isset($item) and !$item->mayEdit($current_user) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
} elseif ( !$item->isOpen() and !$item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif ( isset($item) and !$item->withLimesurveyFunctions() ) {
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
   $form = $class_factory->getClass(CONFIGURATION_LIMESURVEY_FORM,array('environment' => $environment));
   
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
   
   // Save item
    if ( !empty($command) && isOption($command, $translator->getMessage('LIMESURVEY_SAVE_BUTTON')) )
    {

      if ( $form->check() ) {

         // Set modificator and modification date
         $current_user = $environment->getCurrentUserItem();
         $item->setModificatorItem($current_user);
         $item->setModificationDate(getCurrentDateTimeInMySQL());
         
         if ( isset($_POST['ls_activate']) && $_POST['ls_activate'] == "1" )
         {
         	$item->setLimeSurveyActive();
         }
         else
         {
         	$item->setLimeSurveyInactive();
         }
         
         if ( isset($_POST['ls_remote_url']) && !empty($_POST['ls_remote_url']) )
         {
         	$item->setLimeSurveyJsonRpcUrl($_POST['ls_remote_url']);
         }
         else
         {
         	$item->setLimeSurveyJsonRpcUrl('');
         }
         
         if ( isset($_POST['ls_admin_user']) && !empty($_POST['ls_admin_user']) )
         {
         	$item->setLimeSurveyAdminUser($_POST['ls_admin_user']);
         }
         else
         {
         	$item->setLimeSurveyAdminUser('');
         }
         
         if ( isset($_POST['ls_admin_pw']) && !empty($_POST['ls_admin_pw']) )
         {
         	$item->setLimeSurveyAdminPassword($_POST['ls_admin_pw']);
         }
         else
         {
         	$item->setLimeSurveyAdminPassword('');
         }

         $item->save();

         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }
   $form->prepareForm();
   $form->loadValues();

   if (isset($item) and !$item->mayEditRegular($current_user)) {
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