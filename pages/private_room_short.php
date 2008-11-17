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


if ( isset($_GET['delete_room_id']) and !empty($_GET['delete_room_id']) ){
   $manager = $environment->getPrivateRoomManager();
   $room_item =  $manager->getItem($_GET['delete_room_id']);
   if ( !empty($room_item) ){
      $user = $environment->getCurrentUserItem();
      $room_item->setNotShownInPrivateRoomHome($user->getUserID());
      $room_item->save();
   }
   redirect($environment->getCurrentContextID(),'home','index');
}

$user = $environment->getCurrentUserItem();
$manager = $environment->getPrivateRoomManager();
$list2 = $manager->getRelatedContextListForUserOnPrivateRoomHome($user);
$user = $environment->getCurrentUserItem();
$my_room_manager = $environment->getMyRoomManager();
$list_all = $my_room_manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID());
$countAll = $list_all->getCount();


if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
   $from = 1;
}

$i=1;
$status = $current_context->getHomeStatus();
if ($status=='detailed'){
   $end = $from+5;
}else{
   $end = $from+10;
}
$list3 = new cs_list();
$list_item = $list2->getFirst();
while($list_item){
   if ( ($i >= $from) and ($i < $end) ){
      $list3->add($list_item);
      $shown_room_id_array[] = $list_item->getItemID();
   }
   $i++;
   $list_item = $list2->getNext();
}
$countShown = $list2->getCount();



if (!empty($shown_room_id_array)){
   $current_context = $environment->getCurrentContextItem();
   $item_manager = $environment->getItemManager();
   $item_manager->setAgeLimit($current_context->getTimeSpread());
   $shown_item_rubrics_array = $item_manager->getAllUsedRubricsOfRoomList($shown_room_id_array);

   $used_rubrics_for_room_array = array();
   foreach($shown_room_id_array as $room_id){
      foreach($shown_item_rubrics_array as $entry){
         if ($entry['context_id'] == $room_id){
            if ($entry['type'] == 'label'){
               $type = $entry['subtype'];
            } else {
               $type = $entry['type'];
            }
            $used_rubrics_for_room_array[$room_id][] = $type;
         }
      }
   }
}

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $context_item->isOpen();
$title_view = $class_factory->getClass(HOME_TITLE_VIEW,$params);
unset($params);
$page->add($title_view);

if ($status=='detailed'){
#   include_once('classes/cs_private_room_short_view.php');
   include_once('classes/cs_private_room_detailed_short_view.php');
   $short_view = new cs_private_room_short_view($environment,$current_context->isOpen());
   $short_view->setUsedRubricsForRoomsArray($used_rubrics_for_room_array);
   $user_manager = $environment->getUserManager();
   $short_view->setUserForRoomsArray($user_manager->getAllUsersByUserAndRoomIDLimit($user->getUserID(), $shown_room_id_array,$user->getAuthSource()));
}else{
   include_once('classes/cs_private_room_short_view.php');
   $short_view = new cs_private_room_short_view($environment,$current_context->isOpen());
}
include_once('classes/cs_list.php');

$short_view->setFrom($from);
if ($status=='detailed'){
   $short_view->setInterval(5);
}else{
   $short_view->setInterval(10);
}
$short_view->setCountAll($countAll);
$short_view->setCountAllShown($countShown);
$short_view->setList($list3);
$page->addLeft($short_view);
?>