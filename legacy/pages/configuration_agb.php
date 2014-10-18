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
//
$class_factory->includeClass(FORM);
include_once('functions/text_functions.php');

// Get the translator object
$translator = $environment->getTranslationObject();

$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

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
} elseif (!$current_user->isModerator() || !$environment->inPortal()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}else{
   // option contains the name of the submit button, if this
   // script is called as result of a form post
   if (!empty($_POST['option'])) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }


   /* setup the form */
   $form = $class_factory->getClass(CONFIGURATION_AGB_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   $form_view->setAction(curl($environment->getCurrentContextID(),'configuration','agb',''));

   /* we are not called as a result of a form post, so just display the form */
   if ( empty($command) ) {
      $current_context = $environment->getCurrentContextItem();
      $form->setItem($current_context);
      $form->prepareForm();
      $form->loadValues();
   }

   /* we called ourself as result of a form post */
   elseif ( isOption($command,$translator->getMessage('PREFERENCES_SAVE_BUTTON'))) {
      $form->setFormPost($_POST);
      $form->prepareForm();
      $form->loadValues();
      if ( $form->check() ) {
         $commsy = $environment->getCurrentContextItem();
         $languages = $environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            if (!empty($_POST['agb_text_'.mb_strtoupper($language, 'UTF-8')])) {
               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = $_POST['agb_text_'.mb_strtoupper($language, 'UTF-8')];
            } else {
               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = '';
            }
         }

         if(($agbtext_array != $commsy->getAGBTextArray()) or ($_POST['agb_status'] != $commsy->getAGBStatus())) {
            $commsy->setAGBStatus($_POST['agb_status']);
            $commsy->setAGBTextArray($agbtext_array);
            $commsy->setAGBChangeDate();
         }
         $commsy->save();
         $form_view->setItemIsSaved();
         $is_saved = true;

         // editor agb acceptance
         $current_user = $environment->getCurrentUserItem();
         $current_user->setAGBAcceptance();
         $current_user->save();
      }
   }

   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   }else{
      $page->add($form_view);
   }
}

?>