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
$current_context = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Verify parameters for this page
if ( !$current_context->showHomepageLink() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('HOMEPAGE_ERROR_NOT_ACTIVATED'));
   $page->add($errorbox);
   $error = true;
} elseif (!empty($_GET['iid'])) {
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
      $page->add($errorbox);
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

//used to signal which "creator infos" are expanded ...
$creatorInfoStatus = array();
if (!empty($_GET['creator_info_max'])) {
  $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
}

if (!$error) {

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(HOMEPAGE_DETAIL_VIEW,$params);
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

      //Set Read
      $reader_manager = $environment->getReaderManager();
      $reader = $reader_manager->getLatestReader($homepage_item->getItemID());
      if ( empty($reader) or $reader['read_date'] < $homepage_item->getModificationDate() ) {
         $reader_manager->markRead($homepage_item->getItemID(),0);
      }

      $page->add($detail_view);
      $page->setShownHomepageItemID($homepage_item->getItemID());
   }
}
?>