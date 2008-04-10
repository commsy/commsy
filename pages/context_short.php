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

// Get data from database
if ( !isset($room_type) ) {
   include_once('functions/error_functions.php');trigger_error('room_type not set',E_USER_ERROR);
} elseif ( $room_type == CS_PROJECT_TYPE) {
   $manager = $environment->getProjectManager();
} elseif ( $room_type == CS_COMMUNITY_TYPE) {
   $manager = $environment->getCommunityManager();
}

$manager->reset();
if ($environment->inCommunityRoom()) {
   $manager->setContextLimit($environment->getCurrentPortalID());
} else {
   $manager->setContextLimit($environment->getCurrentContextID());
}
if ( $room_type == CS_PROJECT_TYPE and $environment->inCommunityRoom() ) {
   $manager->setCommunityRoomLimit($environment->getCurrentContextID());
}
$count_all = $manager->getCountAll();
$manager->setSortOrder('activity_rev');
$ids = $manager->getIDArray();       // returns an array of item ids
if ( $interval > 0 ) {
   $manager->setIntervalLimit(0,5);
}
$manager->select();
unset($list_context);
$list_context = $manager->get();        // returns a cs_list items

// Prepare view object
$current_context_item = $environment->getCurrentContextItem();
if ($room_type == CS_PROJECT_TYPE) {
   include_once('classes/cs_project_short_view.php');
   $room_short_view = new cs_project_short_view($environment,$current_context_item->isOpen());
} elseif ($room_type == CS_COMMUNITY_TYPE) {
   include_once('classes/cs_community_short_view.php');
   $room_short_view = new cs_community_short_view($environment,$current_context_item->isOpen());
}

// Set data for view
$room_short_view->setList($list_context);
$room_short_view->setCountAll($count_all);

// Add list view to page
if ($environment->inCommunityRoom()){
   $page->addLeft($room_short_view);
} else {
   $page->addRight($room_short_view);
}
unset($list);


$session->setValue('cid'.$environment->getCurrentContextID().'_'.$room_type.'_index_ids', $ids);
$session->setValue('interval', $interval);
?>