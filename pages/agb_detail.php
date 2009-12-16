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

// Get the translator object
$translator = $environment->getTranslationObject();

$current_user = $environment->getCurrentUserItem();
if ( $current_user->isGuest() ) {
   redirect($environment->getCurrentContextID(),'home','index',array());
   exit();
}

// Find out what to do
if ( isset($_POST['option']) ) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if ( isOption($command, $translator->getMessage('AGB_ACCEPTANCE_BUTTON')) ) {
   $current_user = $environment->getCurrentUserItem();
   $current_user->setAGBAcceptance();
   $current_user->save();
   $session_item = $environment->getSessionItem();
   $history = $session_item->getValue('history');
   if ( !empty($history[0]['context'])
        and $history[0]['module'] != 'agb'
      ) {
      $params = $history[0]['parameter'];
      unset($params['cs_modus']);
      redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params);
   } elseif ( !empty($history[1]['context']) ) {
      $params = $history[1]['parameter'];
      unset($params['cs_modus']);
      redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$params);
   } else {
      redirect($environment->getCurrentContextID(),'home','index',array());
   }
   exit();
} elseif ( isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON'))
           or isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_ROOM'))
           or isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_PORTAL'))
              or isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))
         ) {
   $session_item = $environment->getSessionItem();
   $history = $session_item->getValue('history');

   if ( (isOption($command, $translator->getMessage('AGB_ACCEPTANCE_NOT_BUTTON_PORTAL'))) or (isset($_POST['is_no_user']) and (isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')))) ) {
         if ( $environment->inPortal() or $environment->inServer() ) {
          $authentication = $environment->getAuthenticationObject();
          $authentication->delete($current_user->getItemID());
          $session_manager = $environment->getSessionManager();
          $session_manager->delete($session_item->getSessionID(),true);
      }
    }
   //  zur Seite leiten
   if ( !empty($history[0]['context'])
        and $history[0]['module'] != 'agb'
        and !isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))
      ) {
      // Raum betreten
      $params = $history[0]['parameter'];
      unset($params['cs_modus']);
      redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params);
   } elseif ( !empty($history[1]['context']) and !isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) and !isset($_POST['is_no_user']) ) {
      // zurück in vorigen Raum
      $params = $history[1]['parameter'];
      unset($params['cs_modus']);
      if ( $history[1]['context'] == $environment->getCurrentContextID()
           and isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON'))
         ) {
         redirect($environment->getCurrentPortalID(),'home','index',array('room_id' => $environment->getCurrentContextID()));
      } else {
         redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$params);
      }
   } else {
        include_once('pages/context_logout.php');
   }
   exit();
} else {
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(AGB_FORM,$class_params);
   unset($class_params);
   $params = array();
   $form->prepareForm();
   $form->loadValues();
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(FORM_PLAIN_VIEW,$params);
   unset($params);
   $form_view->setAction(curl($environment->getCurrentContextID(),'agb','detail',''));
   $form_view->setForm($form);

   if ( $environment->inPortal() ) {
      $page->addAGBView($form_view);
      $page->setWithoutNavigationLinks();
   } else {
      $page->setWithoutAGBLink();
      $page->add($form_view);
   }
}
?>