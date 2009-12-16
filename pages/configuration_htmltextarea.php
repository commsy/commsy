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


if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
$is_saved = false;
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$context_item->isOpen() and !$context_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') {
   //access granted

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
     $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect($environment->getCurrentContextID(),'configuration','index');
   }

   // Show form and/or save item
    else {
      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_HTMLTEXTAREA_FORM,array('environment' => $environment));
      // display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      } else {
         $form->setItem($context_item);
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON') ) ) {

         $correct = $form->check();
         if ( $correct ) {

           // Set attributes
           if ( isset($_POST['html_status']) ) {
            $context_item->setHtmlTextAreaStatus($_POST['html_status']);
           }
           // Save item
              $context_item->save();
              $form_view->setItemIsSaved();
              $is_saved = true;
         }
      }
      if (isset($context_item) and !$context_item->mayEditRegular($current_user)) {
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
      $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','htmltextarea',''));
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
   }
}
?>