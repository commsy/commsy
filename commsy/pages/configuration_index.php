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

include_once('classes/cs_link.php');
include_once('classes/cs_list.php');

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
     $params['cid'] = $room_item->getItemId();
     redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
} else {
   //access granted
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $list_view = $class_factory->getClass(LINK_PREFERENCE_LIST_VIEW,$params);
   unset($params);

   // room configuration options
   include_once('include/inc_configuration_important_links.php');
   if ( $configuration_important_link_list->getFirst() ){
      $list_view->setImportantConfigurationRoomList($configuration_important_link_list);
   }

   // admin configuration options
   include_once('include/inc_configuration_usage_links.php');
   if ( $usage_link_list->getFirst() ){
      $list_view->setConfigurationUsageList($usage_link_list);
   }

   // admin configuration options
   include_once('include/inc_configuration_rubric_extras_links.php');
   if ( $configuration_rubric_extras_link_list->getFirst() ){
      $list_view->setConfigurationRubricExtrasList($configuration_rubric_extras_link_list);
   }
   // room configuration options
   include_once('include/inc_configuration_room_links.php');
   if ( $room_link_list->getFirst() ){
      $list_view->setConfigurationRoomList($room_link_list);
   }

   // admin configuration options
   include_once('include/inc_configuration_admin_links.php');
   if ( $admin_link_list->getFirst() ){
      $list_view->setConfigurationAdminList($admin_link_list);
   }

   if ( !$environment->inPortal() and !$environment->inServer() ){
      // rubric configuration options
      include_once('include/inc_configuration_rubric_links.php');
      if ( $rubric_link_list->getFirst() ){
         $list_view->setConfigurationRubricList($rubric_link_list);
      }
   }

   // addon configuration options
   include_once('include/inc_configuration_links_addon.php');
   if ( $addon_link_list->getFirst() ){
      $list_view->setAddonList($addon_link_list);
   }

   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addConfigurationListView($list_view);
   } else {
      $page->add($list_view);
   }
}
?>