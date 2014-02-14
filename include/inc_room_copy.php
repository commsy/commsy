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

################################################################
# copy a room (project rooms only)
# like a template (copy data but no users)
# into a new room
################################################################

// initialisation
$old_room_id = $_POST['template_select'];
$room_manager = $environment->getRoomManager();
$old_room = $room_manager->getItem($old_room_id);
$new_room = $item;
$user_manager = $environment->getUserManager();
$creator_item = $user_manager->getItem($new_room->getCreatorID());
if ($creator_item->getContextID() == $new_room->getItemID()) {
   $creator_id = $creator_item->getItemID();
} else {
   $user_manager->resetLimits();
   $user_manager->setContextLimit($new_room->getItemID());
   $user_manager->setUserIDLimit($creator_item->getUserID());
   $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
   $user_manager->setModeratorLimit();
   $user_manager->select();
   $user_list = $user_manager->get();
   if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
      $creator_item = $user_list->getFirst();
      $creator_id = $creator_item->getItemID();
   } else {
      include_once('functions/error_functions.php');
      trigger_error('can not get creator of new room',E_USER_ERROR);
   }
}
$creator_item->setAccountWantMail('yes');
$creator_item->setOpenRoomWantMail('yes');
$creator_item->setPublishMaterialWantMail('yes');
$creator_item->save();

// copy room settings
include_once('include/inc_room_copy_config.php');

// save new room
$new_room->save();

// copy data
include_once('include/inc_room_copy_data.php');
?>