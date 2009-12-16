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

// Verify parameters for this page
if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];
}else {
   include_once('functions/error_functions.php');
   trigger_error('A material item id must be given.', E_USER_ERROR);
}
//check access
$error = false;
$room_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

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
   $error = true;
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $error = true;
}

//access granted
if (!$error) {

   // initialize objects
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $detail_view = $class_factory->getClass(MATERIAL_ADMIN_DETAIL_VIEW,$params);
   unset($params);
   $material_manager = $environment->getMaterialManager();
   $material_item = $material_manager->getItem($current_item_id);
   // set the view's item
   $material_list = $material_manager->getVersionList($current_item_id);
   $detail_view->setVersionList($material_list);

   // set up browsing
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_material_admin_index_ids')) {
      $ids = $session->getValue('cid'.$environment->getCurrentContextID().'_material_admin_index_ids');
   } else {
      $ids = array();
   }
   $detail_view->setBrowseIDs($ids);
   $annotations = $material_item->getAnnotationList();
   $detail_view->setAnnotationList($material_item->getAnnotationList());
      $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = array();
      $secon = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' and $link_name[0] !=$_GET['mod']) {
            switch ($detail_view->_is_perspective($link_name[0])) {
               case true:
                  $first[] = $link_name[0];
               break;
               case false:
                  $second[] = $link_name[0];
               break;
            }
         }
      }
      $room_modules = array_merge($first,$second);
      $rubric_connections = array();
      foreach ($room_modules as $module){
         if ($context_item->withRubric($module) ) {
            $ids = $material_item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }

      $detail_view->setRubricConnections($rubric_connections);


   // highlight search words in detail views
   $session_item = $environment->getSessionItem();
   if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
      $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
      if ( !empty($search_array['search']) ) {
         $detail_view->setSearchText($search_array['search']);
      }
      unset($search_array);
   }

   $page->add($detail_view);
}
?>