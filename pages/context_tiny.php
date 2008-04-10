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
$current_context_item = $environment->getCurrentContextItem();
if ($room_type == CS_PROJECT_TYPE) {
   $manager = $environment->getProjectManager();
   $manager->reset();
   $manager->setContextLimit($environment->getCurrentPortalID());
   $manager->setCommunityRoomLimit($environment->getCurrentContextID());
   $count_all = $manager->getCountAll();
   include_once('classes/cs_tiny_view.php');
   $room_tiny_view = new cs_tiny_view($environment,$current_context_item->isOpen());
   $title = getMessage('COMMON_PROJECT_INDEX');
   $title = ahref_curl( $environment->getCurrentContextID(),
                           CS_PROJECT_TYPE,
                           'index',
                           '',
                           $title,'','','','','','','class="head"');
   $room_tiny_view->setTitle($title);
   $room_tiny_view->setCountAll($count_all);
} elseif ($room_type == CS_COMMUNITY_TYPE) {
   include_once('classes/cs_tiny_view.php');
   $room_tiny_view = new cs_tiny_view($environment,$current_context_item->isOpen());
   $title = getMessage('COMMON_COMMUNITY_INDEX');
   $title = ahref_curl( $environment->getCurrentContextID(),
                           CS_COMMUNITY_TYPE,
                           'index',
                           '',
                           $title,'','','','','','','class="head"');
   $room_tiny_view->setTitle($title);
} else {
   $manager = $environment->getMyRoomManager();
   $manager->reset();
   $user = $environment->getCurrentUserItem();
   $list = new cs_list();
   $list = $manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID());
   $clone_list = clone $list;
   $count_all = $clone_list->getCount();
   include_once('classes/cs_tiny_view.php');
   $room_tiny_view = new cs_tiny_view($environment,$current_context_item->isOpen());
   $title = getMessage('COMMON_PRIVATEROOM_INDEX');
   $title = ahref_curl( $environment->getCurrentContextID(),
                           CS_MYROOM_TYPE,
                           'index',
                           '',
                           $title,'','','','','','','class="head"');
   $room_tiny_view->setTitle($title);
   $room_tiny_view->setCountAll($count_all);
}

// Add list view to page
if ($environment->inPrivateRoom()){
   $page->addRight($room_tiny_view);
}else {
   $page->addLeft($room_tiny_view);
}
unset($list);
?>