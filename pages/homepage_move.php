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

$error = false;

// Get the translator object
$translator = $environment->getTranslationObject();

// Verify parameters for this page
$current_item_id = '';
if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];
   $homepage_manager = $environment->getManager(CS_HOMEPAGE_TYPE);
   $homepage_item = $homepage_manager->getItem($current_item_id);
   if ( !isset($homepage_item) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
      $page->addError($errorbox);
      $error = true;
   }
} elseif (!empty($_POST['iid'])) {
   $current_item_id = $_POST['iid'];
   $homepage_manager = $environment->getManager(CS_HOMEPAGE_TYPE);
   $homepage_item = $homepage_manager->getItem($current_item_id);
   if ( !isset($homepage_item) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
      $page->addError($errorbox);
      $error = true;
   }
} elseif (!empty($_GET['cid'])) {
   $homepage_manager = $environment->getManager(CS_HOMEPAGE_TYPE);
   $homepage_item = $homepage_manager->getRootPageItem($_GET['cid']);
} else {
   include_once('functions/error_functions.php');
   trigger_error('A page item id must be given.', E_USER_ERROR);
   $error = true;
}

// Check access rights
$context_item = $environment->getCurrentContextItem();
if ( $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->addError($errorbox);
   $error = true;
} elseif ( $current_item_id != 'NEW' and !isset($homepage_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->addError($errorbox);
   $error = true;
} elseif ( !(($current_item_id == 'NEW' and $current_user->isUser()) or
             ($current_item_id != 'NEW' and isset($homepage_item) and
              $homepage_item->mayEdit($current_user))) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->addError($errorbox);
   $error = true;
}

// Access granted

if (!$error) {
   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('HOMEPAGE_MOVE_END_BUTTON')) ) {
      $params = array();
      if ( !empty($current_item_id) ) {
         $params['iid'] = $current_item_id;
      }
      redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'detail', $params);
   }

   if ( isset($_GET['direction']) ) {
      $move = $_GET['direction'];
   } else {
      $move = '';
   }

   // move page
   if ( !empty($move) ) {
      if ($move == 'up') {
         $homepage_item->moveUp();
      } elseif ($move == 'down') {
         $homepage_item->moveDown();
      } elseif ($move == 'left') {
         $homepage_item->moveLeft();
      } elseif ($move == 'right') {
         $homepage_item->moveRight();
      }
   }

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $detail_view = $class_factory->getClass(HOMEPAGE_MOVE_VIEW,$params);
   unset($params);

   // set the view's item
   $current_user = $environment->getCurrentUser();
   if ( $homepage_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } else {
      $detail_view->setItem($homepage_item);

      $page->add($detail_view);
      $page->setShownHomepageItemID($homepage_item->getItemID());
   }
}
?>