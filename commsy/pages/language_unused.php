<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

// Get the translator object
$translator = $environment->getTranslationObject();

$current_user = $environment->getCurrentUser();
if ( !isset($c_message_management)
     or ( isset($c_message_management)
          and !$c_message_management
        )
     or $current_user->isGuest()
   ) {
   redirect($environment->getCurrentContextID(),'home','index');
} else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   if ( !empty($command) and ( isOption($command, $translator->getMessage('LANGUAGE_DELETE_UNUSED_BUTTON')) ) ) {
      if ( !empty($_POST['unused_tags']) ) {
         $message_array = $translator->getCompleteMessageArray();
         foreach ( $_POST['unused_tags'] as $tag ) {
            unset($message_array[$tag]);
         }
         $translator->setMessageArray($message_array);
         $translator->saveMessages();
      }
   }

   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(LANGUAGE_UNUSED_FORM,$class_params);
   unset($class_params);
   $form->prepareForm();
   $form->loadValues();

   $class_params = array();
   $class_params['environment'] = $environment;
   $class_params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
   unset($class_params);

   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>