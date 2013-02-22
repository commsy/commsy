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

$user_manager = $environment->getUserManager();
$user_manager->resetLimits();
$user_item = $user_manager->getRootUser();

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($user_item)) {
   include_once('functions/error_functions.php');
   trigger_error('root user is allready know in database',E_USER_ERROR);
} else {
   if (!empty($_POST['option'])) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // include form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(SERVER_INITIALIZE_FORM,$class_params);
      unset($class_params);

   // cancel
   if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect($environment->getCurrentContextID(), 'home', 'index', '');
   }

   // save initial information
   else {
      if (isset($_POST)) {
         $form->setFormPost($_POST);
      }
      $form->prepareForm();
      $form->loadValues();
      if (!empty($command)) {
         if ($form->check()) {
            // save auth information
            include_once('classes/cs_auth_item.php');
            $auth_item = new cs_auth_item();
            $auth_item->setUserID($_POST['user_id']);
            $auth_item->setPassword($_POST['password']);
            $auth_item->setFirstname($_POST['firstname']);
            $auth_item->setLastname($_POST['lastname']);
            $auth_item->setLanguage($_POST['language']);
            $auth_item->setEmail($_POST['email']);
            $auth_item->setCommSyID($environment->getCurrentContextID());
            $authentication = $environment->getAuthenticationObject();
            $authentication->save($auth_item);

            // save user information
            $user = $authentication->getUserItem();
            $user->makeModerator();
            $user->save();

            // create session
            $session = new cs_session_item();
            $session->createSessionID($_POST['user_id']);

            // redirect to initialize first portal
            redirect($environment->getCurrentContextID(), 'campus', 'initialize', '');
         }
      }

      // display form
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
      unset($class_params);
      $form_view->setAction(curl($environment->getCurrentContextID(),'server','initialize',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
$page->setPageName($c_meta['name']);
$page->setRoomName($translator->getMessage('SERVER_INITIALIZE_HEADER_DESC'));
$page->setWithoutPersonalArea();
$page->setWithoutNavigationLinks();
?>